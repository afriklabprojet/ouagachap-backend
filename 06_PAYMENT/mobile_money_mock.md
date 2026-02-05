# 💰 Mobile Money Mock System - OUAGA CHAP

> Complete mock implementation for Mobile Money payments (Orange Money & Moov Money)

---

## 1. Overview

### Purpose
For MVP development and testing, we implement a **mock Mobile Money system** that simulates the real payment flow without connecting to actual providers. This allows:

- Full end-to-end testing
- Development without provider API access
- Demo capabilities
- Testing edge cases and error scenarios

### Supported Providers (Mock)
| Provider | Country | USSD Code (Real) |
|----------|---------|------------------|
| Orange Money | Burkina Faso | *144# |
| Moov Money | Burkina Faso | *555# |

---

## 2. Payment Flow

### 2.1 Complete Flow Diagram

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        MOBILE MONEY MOCK FLOW                                │
└─────────────────────────────────────────────────────────────────────────────┘

    CUSTOMER APP              BACKEND API              MOCK PROVIDER
         │                        │                        │
         │ 1. Create Order        │                        │
         │───────────────────────▶│                        │
         │                        │                        │
         │◀───────────────────────│                        │
         │    Order created       │                        │
         │    (status: pending)   │                        │
         │                        │                        │
         │ 2. Init Payment        │                        │
         │    {provider, phone}   │                        │
         │───────────────────────▶│                        │
         │                        │ 3. Create Payment      │
         │                        │    (status: pending)   │
         │                        │───────────────────────▶│
         │                        │                        │
         │                        │◀───────────────────────│
         │                        │    Mock Transaction ID │
         │◀───────────────────────│                        │
         │    USSD instructions   │                        │
         │                        │                        │
         │ 4. User "confirms"     │                        │
         │    (mock: PIN entry)   │                        │
         │───────────────────────▶│                        │
         │                        │ 5. Validate Mock PIN   │
         │                        │───────────────────────▶│
         │                        │                        │
         │                        │◀───────────────────────│
         │                        │    Success/Failure     │
         │                        │                        │
         │                        │ 6. Update Payment      │
         │                        │    (status: completed) │
         │                        │                        │
         │                        │ 7. Update Order        │
         │                        │    Find courier        │
         │                        │                        │
         │◀───────────────────────│                        │
         │    Payment confirmed   │                        │
         │                        │                        │
```

### 2.2 State Machine

```
                    ┌─────────────┐
                    │   PENDING   │
                    └──────┬──────┘
                           │
            ┌──────────────┼──────────────┐
            │              │              │
            ▼              ▼              ▼
     ┌────────────┐ ┌────────────┐ ┌────────────┐
     │ PROCESSING │ │  CANCELLED │ │   EXPIRED  │
     └─────┬──────┘ └────────────┘ └────────────┘
           │
     ┌─────┴─────┐
     │           │
     ▼           ▼
┌──────────┐ ┌──────────┐
│ COMPLETED│ │  FAILED  │
└──────────┘ └──────────┘
     │
     ▼
┌──────────┐
│ REFUNDED │ (if order cancelled after payment)
└──────────┘
```

---

## 3. Backend Implementation

### 3.1 Mock Payment Service

```php
<?php
// app/Services/Payment/MobileMoneyMockService.php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Order;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MobileMoneyMockService implements PaymentProviderInterface
{
    // Mock configuration
    private const MOCK_SUCCESS_RATE = 0.95; // 95% success rate
    private const MOCK_PROCESSING_TIME = 2; // seconds
    private const MOCK_PIN = '1234'; // Universal test PIN
    
    // Test phone numbers for specific scenarios
    private const TEST_PHONES = [
        '+22670000001' => 'success',      // Always succeeds
        '+22670000002' => 'failed',       // Always fails
        '+22670000003' => 'timeout',      // Simulates timeout
        '+22670000004' => 'insufficient', // Insufficient funds
    ];

    /**
     * Initialize a payment transaction
     */
    public function initializePayment(Order $order, string $provider, string $phone): Payment
    {
        // Validate provider
        if (!in_array($provider, ['orange_money', 'moov_money'])) {
            throw new \InvalidArgumentException("Invalid provider: {$provider}");
        }

        // Create payment record
        $payment = Payment::create([
            'order_id' => $order->id,
            'amount' => $order->total_price,
            'currency' => 'XOF',
            'provider' => $provider,
            'phone' => $phone,
            'status' => 'pending',
            'transaction_id' => $this->generateMockTransactionId($provider),
            'initiated_at' => now(),
        ]);

        return $payment;
    }

    /**
     * Process payment confirmation (mock)
     */
    public function confirmPayment(Payment $payment, string $pin): array
    {
        // Simulate processing time
        sleep(self::MOCK_PROCESSING_TIME);

        // Check for test phone scenarios
        $testScenario = self::TEST_PHONES[$payment->phone] ?? null;
        
        if ($testScenario) {
            return $this->handleTestScenario($payment, $testScenario);
        }

        // Validate PIN (mock)
        if ($pin !== self::MOCK_PIN) {
            return $this->failPayment($payment, 'PIN incorrect');
        }

        // Random success/failure based on mock success rate
        if (mt_rand(1, 100) / 100 > self::MOCK_SUCCESS_RATE) {
            return $this->failPayment($payment, 'Transaction refusée par l\'opérateur');
        }

        return $this->completePayment($payment);
    }

    /**
     * Generate mock transaction ID
     */
    private function generateMockTransactionId(string $provider): string
    {
        $prefix = $provider === 'orange_money' ? 'OM' : 'MV';
        $timestamp = now()->format('YmdHis');
        $random = strtoupper(Str::random(6));
        
        return "MOCK_{$prefix}_{$timestamp}_{$random}";
    }

    /**
     * Complete payment successfully
     */
    private function completePayment(Payment $payment): array
    {
        $payment->update([
            'status' => 'completed',
            'completed_at' => now(),
            'provider_reference' => 'MOCK_REF_' . Str::random(10),
        ]);

        // Dispatch event to update order status
        event(new \App\Events\PaymentCompleted($payment));

        return [
            'success' => true,
            'payment_id' => $payment->id,
            'status' => 'completed',
            'transaction_id' => $payment->transaction_id,
            'message' => 'Paiement confirmé avec succès',
        ];
    }

    /**
     * Fail payment with reason
     */
    private function failPayment(Payment $payment, string $reason): array
    {
        $payment->update([
            'status' => 'failed',
            'failure_reason' => $reason,
        ]);

        return [
            'success' => false,
            'payment_id' => $payment->id,
            'status' => 'failed',
            'error' => $reason,
        ];
    }

    /**
     * Handle test phone scenarios
     */
    private function handleTestScenario(Payment $payment, string $scenario): array
    {
        switch ($scenario) {
            case 'success':
                return $this->completePayment($payment);
            
            case 'failed':
                return $this->failPayment($payment, 'Transaction refusée');
            
            case 'timeout':
                sleep(30); // Simulate timeout
                return $this->failPayment($payment, 'Délai dépassé');
            
            case 'insufficient':
                return $this->failPayment($payment, 'Solde insuffisant');
            
            default:
                return $this->completePayment($payment);
        }
    }

    /**
     * Get USSD instructions for user
     */
    public function getUssdInstructions(Payment $payment): array
    {
        $ussdCodes = [
            'orange_money' => '*144*4*6*' . (int)$payment->amount . '#',
            'moov_money' => '*555*1*' . (int)$payment->amount . '#',
        ];

        $instructions = [
            'orange_money' => "1. Composez {$ussdCodes['orange_money']}\n2. Entrez votre code PIN\n3. Confirmez le paiement",
            'moov_money' => "1. Composez {$ussdCodes['moov_money']}\n2. Entrez votre code PIN\n3. Confirmez le paiement",
        ];

        return [
            'provider' => $payment->provider,
            'amount' => $payment->amount,
            'currency' => 'FCFA',
            'ussd_code' => $ussdCodes[$payment->provider],
            'instructions' => $instructions[$payment->provider],
            'mock_pin' => self::MOCK_PIN, // Only for development!
            'expires_in' => 300, // 5 minutes
        ];
    }

    /**
     * Process refund (mock)
     */
    public function refundPayment(Payment $payment): array
    {
        if ($payment->status !== 'completed') {
            throw new \Exception('Can only refund completed payments');
        }

        $payment->update([
            'status' => 'refunded',
            'refunded_at' => now(),
        ]);

        return [
            'success' => true,
            'payment_id' => $payment->id,
            'status' => 'refunded',
            'refund_amount' => $payment->amount,
            'message' => 'Remboursement effectué',
        ];
    }

    /**
     * Check payment status
     */
    public function checkStatus(Payment $payment): array
    {
        return [
            'payment_id' => $payment->id,
            'status' => $payment->status,
            'amount' => $payment->amount,
            'provider' => $payment->provider,
            'transaction_id' => $payment->transaction_id,
            'initiated_at' => $payment->initiated_at,
            'completed_at' => $payment->completed_at,
        ];
    }
}
```

### 3.2 Payment Controller

```php
<?php
// app/Http/Controllers/Api/PaymentController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InitPaymentRequest;
use App\Http\Requests\ConfirmPaymentRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\MobileMoneyMockService;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function __construct(
        private MobileMoneyMockService $paymentService
    ) {}

    /**
     * Initialize payment for an order
     * POST /api/v1/payments
     */
    public function initialize(InitPaymentRequest $request): JsonResponse
    {
        $order = Order::where('uuid', $request->order_uuid)
            ->where('customer_id', auth()->id())
            ->firstOrFail();

        // Check if order can be paid
        if ($order->status !== 'pending') {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ORDER_NOT_PAYABLE',
                    'message' => 'Cette commande ne peut pas être payée',
                ],
            ], 400);
        }

        // Check for existing pending payment
        $existingPayment = Payment::where('order_id', $order->id)
            ->where('status', 'pending')
            ->first();

        if ($existingPayment) {
            return response()->json([
                'success' => true,
                'message' => 'Paiement déjà en cours',
                'data' => array_merge(
                    ['payment_id' => $existingPayment->id],
                    $this->paymentService->getUssdInstructions($existingPayment)
                ),
            ]);
        }

        // Initialize new payment
        $payment = $this->paymentService->initializePayment(
            $order,
            $request->provider,
            $request->phone
        );

        return response()->json([
            'success' => true,
            'message' => 'Paiement initié',
            'data' => array_merge(
                ['payment_id' => $payment->id],
                $this->paymentService->getUssdInstructions($payment)
            ),
        ]);
    }

    /**
     * Confirm payment (mock PIN entry)
     * POST /api/v1/payments/{id}/confirm
     */
    public function confirm(ConfirmPaymentRequest $request, int $id): JsonResponse
    {
        $payment = Payment::where('id', $id)
            ->whereHas('order', function ($q) {
                $q->where('customer_id', auth()->id());
            })
            ->firstOrFail();

        if ($payment->status !== 'pending') {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PAYMENT_NOT_PENDING',
                    'message' => 'Ce paiement a déjà été traité',
                ],
            ], 400);
        }

        $result = $this->paymentService->confirmPayment($payment, $request->pin);

        $statusCode = $result['success'] ? 200 : 400;

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'] ?? $result['error'] ?? '',
            'data' => $result,
        ], $statusCode);
    }

    /**
     * Get payment status
     * GET /api/v1/payments/{id}
     */
    public function show(int $id): JsonResponse
    {
        $payment = Payment::where('id', $id)
            ->whereHas('order', function ($q) {
                $q->where('customer_id', auth()->id());
            })
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $this->paymentService->checkStatus($payment),
        ]);
    }
}
```

### 3.3 Form Requests

```php
<?php
// app/Http/Requests/InitPaymentRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitPaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'order_uuid' => 'required|uuid|exists:orders,uuid',
            'provider' => 'required|in:orange_money,moov_money',
            'phone' => [
                'required',
                'string',
                'regex:/^\+226[0-9]{8}$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'order_uuid.required' => 'La commande est requise',
            'order_uuid.exists' => 'Commande introuvable',
            'provider.required' => 'Le fournisseur de paiement est requis',
            'provider.in' => 'Fournisseur non supporté',
            'phone.required' => 'Le numéro de téléphone est requis',
            'phone.regex' => 'Format: +226XXXXXXXX',
        ];
    }
}
```

```php
<?php
// app/Http/Requests/ConfirmPaymentRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmPaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'pin' => 'required|string|size:4',
        ];
    }

    public function messages(): array
    {
        return [
            'pin.required' => 'Le code PIN est requis',
            'pin.size' => 'Le code PIN doit contenir 4 chiffres',
        ];
    }
}
```

---

## 4. Flutter Implementation

### 4.1 Payment Service

```dart
// lib/features/payment/data/datasources/payment_remote_datasource.dart

import 'package:dio/dio.dart';
import '../models/payment_model.dart';

class PaymentRemoteDataSource {
  final Dio _dio;

  PaymentRemoteDataSource(this._dio);

  Future<PaymentInitResponse> initializePayment({
    required String orderUuid,
    required String provider,
    required String phone,
  }) async {
    final response = await _dio.post('/payments', data: {
      'order_uuid': orderUuid,
      'provider': provider,
      'phone': phone,
    });

    return PaymentInitResponse.fromJson(response.data['data']);
  }

  Future<PaymentConfirmResponse> confirmPayment({
    required int paymentId,
    required String pin,
  }) async {
    final response = await _dio.post('/payments/$paymentId/confirm', data: {
      'pin': pin,
    });

    return PaymentConfirmResponse.fromJson(response.data['data']);
  }

  Future<PaymentStatus> getPaymentStatus(int paymentId) async {
    final response = await _dio.get('/payments/$paymentId');
    return PaymentStatus.fromJson(response.data['data']);
  }
}
```

### 4.2 Payment Models

```dart
// lib/features/payment/data/models/payment_model.dart

class PaymentInitResponse {
  final int paymentId;
  final String provider;
  final double amount;
  final String currency;
  final String ussdCode;
  final String instructions;
  final String mockPin; // Only for development!
  final int expiresIn;

  PaymentInitResponse({
    required this.paymentId,
    required this.provider,
    required this.amount,
    required this.currency,
    required this.ussdCode,
    required this.instructions,
    required this.mockPin,
    required this.expiresIn,
  });

  factory PaymentInitResponse.fromJson(Map<String, dynamic> json) {
    return PaymentInitResponse(
      paymentId: json['payment_id'],
      provider: json['provider'],
      amount: (json['amount'] as num).toDouble(),
      currency: json['currency'],
      ussdCode: json['ussd_code'],
      instructions: json['instructions'],
      mockPin: json['mock_pin'] ?? '',
      expiresIn: json['expires_in'],
    );
  }
}

class PaymentConfirmResponse {
  final bool success;
  final int paymentId;
  final String status;
  final String? transactionId;
  final String? message;
  final String? error;

  PaymentConfirmResponse({
    required this.success,
    required this.paymentId,
    required this.status,
    this.transactionId,
    this.message,
    this.error,
  });

  factory PaymentConfirmResponse.fromJson(Map<String, dynamic> json) {
    return PaymentConfirmResponse(
      success: json['success'],
      paymentId: json['payment_id'],
      status: json['status'],
      transactionId: json['transaction_id'],
      message: json['message'],
      error: json['error'],
    );
  }
}

enum PaymentProvider {
  orangeMoney('orange_money', 'Orange Money', 'assets/icons/orange_money.png'),
  moovMoney('moov_money', 'Moov Money', 'assets/icons/moov_money.png');

  final String value;
  final String displayName;
  final String iconPath;

  const PaymentProvider(this.value, this.displayName, this.iconPath);
}
```

### 4.3 Payment BLoC

```dart
// lib/features/payment/presentation/bloc/payment_bloc.dart

import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:equatable/equatable.dart';
import '../../domain/usecases/initialize_payment.dart';
import '../../domain/usecases/confirm_payment.dart';

// Events
abstract class PaymentEvent extends Equatable {
  @override
  List<Object?> get props => [];
}

class InitializePaymentEvent extends PaymentEvent {
  final String orderUuid;
  final String provider;
  final String phone;

  InitializePaymentEvent({
    required this.orderUuid,
    required this.provider,
    required this.phone,
  });

  @override
  List<Object?> get props => [orderUuid, provider, phone];
}

class ConfirmPaymentEvent extends PaymentEvent {
  final int paymentId;
  final String pin;

  ConfirmPaymentEvent({required this.paymentId, required this.pin});

  @override
  List<Object?> get props => [paymentId, pin];
}

// States
abstract class PaymentState extends Equatable {
  @override
  List<Object?> get props => [];
}

class PaymentInitial extends PaymentState {}

class PaymentLoading extends PaymentState {}

class PaymentInitialized extends PaymentState {
  final PaymentInitResponse payment;

  PaymentInitialized(this.payment);

  @override
  List<Object?> get props => [payment];
}

class PaymentConfirmed extends PaymentState {
  final PaymentConfirmResponse result;

  PaymentConfirmed(this.result);

  @override
  List<Object?> get props => [result];
}

class PaymentFailed extends PaymentState {
  final String message;

  PaymentFailed(this.message);

  @override
  List<Object?> get props => [message];
}

// BLoC
class PaymentBloc extends Bloc<PaymentEvent, PaymentState> {
  final InitializePayment initializePayment;
  final ConfirmPayment confirmPayment;

  PaymentBloc({
    required this.initializePayment,
    required this.confirmPayment,
  }) : super(PaymentInitial()) {
    on<InitializePaymentEvent>(_onInitializePayment);
    on<ConfirmPaymentEvent>(_onConfirmPayment);
  }

  Future<void> _onInitializePayment(
    InitializePaymentEvent event,
    Emitter<PaymentState> emit,
  ) async {
    emit(PaymentLoading());

    final result = await initializePayment(InitializePaymentParams(
      orderUuid: event.orderUuid,
      provider: event.provider,
      phone: event.phone,
    ));

    result.fold(
      (failure) => emit(PaymentFailed(failure.message)),
      (payment) => emit(PaymentInitialized(payment)),
    );
  }

  Future<void> _onConfirmPayment(
    ConfirmPaymentEvent event,
    Emitter<PaymentState> emit,
  ) async {
    emit(PaymentLoading());

    final result = await confirmPayment(ConfirmPaymentParams(
      paymentId: event.paymentId,
      pin: event.pin,
    ));

    result.fold(
      (failure) => emit(PaymentFailed(failure.message)),
      (response) {
        if (response.success) {
          emit(PaymentConfirmed(response));
        } else {
          emit(PaymentFailed(response.error ?? 'Paiement échoué'));
        }
      },
    );
  }
}
```

### 4.4 Payment UI

```dart
// lib/features/payment/presentation/pages/payment_page.dart

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../bloc/payment_bloc.dart';
import '../widgets/provider_selector.dart';
import '../widgets/pin_entry_dialog.dart';

class PaymentPage extends StatefulWidget {
  final String orderUuid;
  final double amount;

  const PaymentPage({
    Key? key,
    required this.orderUuid,
    required this.amount,
  }) : super(key: key);

  @override
  State<PaymentPage> createState() => _PaymentPageState();
}

class _PaymentPageState extends State<PaymentPage> {
  PaymentProvider? _selectedProvider;
  final _phoneController = TextEditingController();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Paiement'),
      ),
      body: BlocConsumer<PaymentBloc, PaymentState>(
        listener: (context, state) {
          if (state is PaymentInitialized) {
            _showPinEntryDialog(context, state.payment);
          } else if (state is PaymentConfirmed) {
            _showSuccessDialog(context);
          } else if (state is PaymentFailed) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text(state.message),
                backgroundColor: Colors.red,
              ),
            );
          }
        },
        builder: (context, state) {
          return SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                // Amount display
                _buildAmountCard(),
                const SizedBox(height: 24),

                // Provider selection
                const Text(
                  'Choisir le mode de paiement',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 12),
                ProviderSelector(
                  selectedProvider: _selectedProvider,
                  onProviderSelected: (provider) {
                    setState(() => _selectedProvider = provider);
                  },
                ),
                const SizedBox(height: 24),

                // Phone number
                TextField(
                  controller: _phoneController,
                  keyboardType: TextInputType.phone,
                  decoration: const InputDecoration(
                    labelText: 'Numéro Mobile Money',
                    hintText: '+226 70 00 00 00',
                    prefixIcon: Icon(Icons.phone),
                    border: OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 32),

                // Pay button
                ElevatedButton(
                  onPressed: state is PaymentLoading ? null : _onPayPressed,
                  style: ElevatedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    backgroundColor: Theme.of(context).primaryColor,
                  ),
                  child: state is PaymentLoading
                      ? const CircularProgressIndicator(color: Colors.white)
                      : Text(
                          'Payer ${widget.amount.toInt()} FCFA',
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                ),

                // Mock info (development only)
                if (const bool.fromEnvironment('DEBUG', defaultValue: true))
                  _buildMockInfo(),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _buildAmountCard() {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          children: [
            const Text(
              'Montant à payer',
              style: TextStyle(fontSize: 14, color: Colors.grey),
            ),
            const SizedBox(height: 8),
            Text(
              '${widget.amount.toInt()} FCFA',
              style: const TextStyle(
                fontSize: 32,
                fontWeight: FontWeight.bold,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildMockInfo() {
    return Container(
      margin: const EdgeInsets.only(top: 24),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.yellow.shade100,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: Colors.yellow.shade700),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: const [
          Text(
            '⚠️ Mode Test',
            style: TextStyle(fontWeight: FontWeight.bold),
          ),
          SizedBox(height: 4),
          Text('PIN de test: 1234'),
          Text('Téléphones de test:'),
          Text('• +22670000001 → Succès'),
          Text('• +22670000002 → Échec'),
          Text('• +22670000004 → Solde insuffisant'),
        ],
      ),
    );
  }

  void _onPayPressed() {
    if (_selectedProvider == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Veuillez choisir un mode de paiement')),
      );
      return;
    }

    final phone = _phoneController.text.trim();
    if (phone.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Veuillez entrer votre numéro')),
      );
      return;
    }

    context.read<PaymentBloc>().add(InitializePaymentEvent(
          orderUuid: widget.orderUuid,
          provider: _selectedProvider!.value,
          phone: phone,
        ));
  }

  void _showPinEntryDialog(BuildContext context, PaymentInitResponse payment) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => PinEntryDialog(
        instructions: payment.instructions,
        ussdCode: payment.ussdCode,
        mockPin: payment.mockPin,
        onPinEntered: (pin) {
          Navigator.of(ctx).pop();
          context.read<PaymentBloc>().add(ConfirmPaymentEvent(
                paymentId: payment.paymentId,
                pin: pin,
              ));
        },
      ),
    );
  }

  void _showSuccessDialog(BuildContext context) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => AlertDialog(
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: const [
            Icon(Icons.check_circle, color: Colors.green, size: 64),
            SizedBox(height: 16),
            Text(
              'Paiement réussi!',
              style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
            ),
            SizedBox(height: 8),
            Text('Votre commande est en cours de traitement.'),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () {
              Navigator.of(ctx).pop();
              Navigator.of(context).pop(true); // Return success
            },
            child: const Text('Continuer'),
          ),
        ],
      ),
    );
  }
}
```

---

## 5. Test Scenarios

### 5.1 Test Phone Numbers

| Phone Number | Behavior | Use Case |
|--------------|----------|----------|
| `+22670000001` | Always succeeds | Happy path testing |
| `+22670000002` | Always fails | Error handling testing |
| `+22670000003` | Timeout (30s) | Timeout handling |
| `+22670000004` | Insufficient funds | Specific error message |
| Any other | 95% success rate | Realistic testing |

### 5.2 Test Cases

```php
<?php
// tests/Feature/PaymentTest.php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\Payment;

class PaymentTest extends TestCase
{
    /** @test */
    public function customer_can_initialize_payment()
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'pending',
            'total_price' => 1500,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/payments', [
                'order_uuid' => $order->uuid,
                'provider' => 'orange_money',
                'phone' => '+22670123456',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'provider' => 'orange_money',
                    'amount' => 1500,
                ],
            ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function payment_succeeds_with_correct_pin()
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'pending',
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/payments/{$payment->id}/confirm", [
                'pin' => '1234', // Mock PIN
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'completed',
                ],
            ]);
    }

    /** @test */
    public function payment_fails_with_wrong_pin()
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/payments/{$payment->id}/confirm", [
                'pin' => '0000', // Wrong PIN
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function test_phone_always_succeeds()
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'pending',
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'status' => 'pending',
            'phone' => '+22670000001', // Always success
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/payments/{$payment->id}/confirm", [
                'pin' => 'any',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function test_phone_always_fails()
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'status' => 'pending',
            'phone' => '+22670000002', // Always fails
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/payments/{$payment->id}/confirm", [
                'pin' => '1234',
            ]);

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    }
}
```

---

## 6. Transition to Real Providers

### 6.1 Provider Interface

```php
<?php
// app/Services/Payment/PaymentProviderInterface.php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;

interface PaymentProviderInterface
{
    public function initializePayment(Order $order, string $provider, string $phone): Payment;
    public function confirmPayment(Payment $payment, string $pin): array;
    public function refundPayment(Payment $payment): array;
    public function checkStatus(Payment $payment): array;
    public function getUssdInstructions(Payment $payment): array;
}
```

### 6.2 Factory Pattern for Providers

```php
<?php
// app/Services/Payment/PaymentProviderFactory.php

namespace App\Services\Payment;

class PaymentProviderFactory
{
    public static function create(): PaymentProviderInterface
    {
        // Switch between mock and real based on environment
        if (config('services.payment.use_mock', true)) {
            return new MobileMoneyMockService();
        }

        // Real providers (Phase 2)
        return match (config('services.payment.provider')) {
            'orange' => new OrangeMoneyService(),
            'moov' => new MoovMoneyService(),
            default => new MobileMoneyMockService(),
        };
    }
}
```

### 6.3 Configuration

```php
<?php
// config/services.php

return [
    // ... other services
    
    'payment' => [
        'use_mock' => env('PAYMENT_USE_MOCK', true),
        'provider' => env('PAYMENT_PROVIDER', 'mock'),
        
        'orange_money' => [
            'merchant_id' => env('ORANGE_MONEY_MERCHANT_ID'),
            'api_key' => env('ORANGE_MONEY_API_KEY'),
            'api_secret' => env('ORANGE_MONEY_API_SECRET'),
            'callback_url' => env('ORANGE_MONEY_CALLBACK_URL'),
        ],
        
        'moov_money' => [
            'merchant_id' => env('MOOV_MONEY_MERCHANT_ID'),
            'api_key' => env('MOOV_MONEY_API_KEY'),
            'callback_url' => env('MOOV_MONEY_CALLBACK_URL'),
        ],
    ],
];
```

---

## 7. Courier Withdrawal Mock

### 7.1 Withdrawal Service

```php
<?php
// app/Services/Payment/WithdrawalMockService.php

namespace App\Services\Payment;

use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Support\Str;

class WithdrawalMockService
{
    private const MIN_WITHDRAWAL = 1000;
    private const MOCK_PROCESSING_TIME = 3;

    public function requestWithdrawal(
        User $courier,
        float $amount,
        string $provider,
        string $phone
    ): Withdrawal {
        // Validate amount
        if ($amount < self::MIN_WITHDRAWAL) {
            throw new \Exception("Montant minimum: " . self::MIN_WITHDRAWAL . " FCFA");
        }

        if ($amount > $courier->wallet_balance) {
            throw new \Exception("Solde insuffisant");
        }

        // Check for pending withdrawal
        $pending = Withdrawal::where('courier_id', $courier->id)
            ->where('status', 'pending')
            ->exists();

        if ($pending) {
            throw new \Exception("Vous avez déjà une demande de retrait en cours");
        }

        // Deduct from wallet
        $courier->decrement('wallet_balance', $amount);

        // Create withdrawal
        return Withdrawal::create([
            'courier_id' => $courier->id,
            'amount' => $amount,
            'currency' => 'XOF',
            'provider' => $provider,
            'phone' => $phone,
            'status' => 'pending',
            'transaction_id' => 'WD_' . strtoupper(Str::random(12)),
            'requested_at' => now(),
        ]);
    }

    public function processWithdrawal(Withdrawal $withdrawal, User $admin): array
    {
        sleep(self::MOCK_PROCESSING_TIME);

        $withdrawal->update([
            'status' => 'completed',
            'processed_by' => $admin->id,
            'processed_at' => now(),
            'provider_reference' => 'MOCK_WD_' . Str::random(8),
        ]);

        return [
            'success' => true,
            'withdrawal_id' => $withdrawal->id,
            'status' => 'completed',
            'message' => 'Retrait effectué avec succès',
        ];
    }
}
```

---

*Document technique - Dernière mise à jour: Janvier 2026*

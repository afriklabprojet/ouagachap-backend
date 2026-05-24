<?php

namespace App\DTOs;

use Illuminate\Http\Request;

readonly class CreateOrderDTO
{
    public function __construct(
        public string  $pickupAddress,
        public float   $pickupLatitude,
        public float   $pickupLongitude,
        public string  $pickupContactName,
        public string  $pickupContactPhone,
        public string  $dropoffAddress,
        public float   $dropoffLatitude,
        public float   $dropoffLongitude,
        public string  $dropoffContactName,
        public string  $dropoffContactPhone,
        public ?string $packageDescription,
        public ?string $pickupInstructions = null,
        public ?string $dropoffInstructions = null,
        public string  $packageSize = 'medium',
        public string  $paymentMethod = 'cash',
        public ?int    $zoneId = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            pickupAddress:       $request->input('pickup_address'),
            pickupLatitude:      (float) $request->input('pickup_latitude'),
            pickupLongitude:     (float) $request->input('pickup_longitude'),
            pickupContactName:   $request->input('pickup_contact_name'),
            pickupContactPhone:  $request->input('pickup_contact_phone'),
            dropoffAddress:      $request->input('dropoff_address'),
            dropoffLatitude:     (float) $request->input('dropoff_latitude'),
            dropoffLongitude:    (float) $request->input('dropoff_longitude'),
            dropoffContactName:  $request->input('dropoff_contact_name'),
            dropoffContactPhone: $request->input('dropoff_contact_phone'),
            packageDescription:  $request->input('package_description') ?: null,
            pickupInstructions:  $request->input('pickup_instructions'),
            dropoffInstructions: $request->input('dropoff_instructions'),
            packageSize:         $request->input('package_size', 'medium'),
            paymentMethod:       $request->input('payment_method', 'cash'),
            zoneId:              $request->input('zone_id') !== null ? (int) $request->input('zone_id') : null,
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            pickupAddress:       $data['pickup_address'],
            pickupLatitude:      (float) $data['pickup_latitude'],
            pickupLongitude:     (float) $data['pickup_longitude'],
            pickupContactName:   $data['pickup_contact_name'] ?? '',
            pickupContactPhone:  $data['pickup_contact_phone'] ?? '',
            dropoffAddress:      $data['dropoff_address'],
            dropoffLatitude:     (float) $data['dropoff_latitude'],
            dropoffLongitude:    (float) $data['dropoff_longitude'],
            dropoffContactName:  $data['dropoff_contact_name'],
            dropoffContactPhone: $data['dropoff_contact_phone'],
            packageDescription:  isset($data['package_description']) && $data['package_description'] !== '' ? $data['package_description'] : null,
            pickupInstructions:  $data['pickup_instructions'] ?? null,
            dropoffInstructions: $data['dropoff_instructions'] ?? null,
            packageSize:         $data['package_size'] ?? 'medium',
            paymentMethod:       $data['payment_method'] ?? 'cash',
            zoneId:              isset($data['zone_id']) ? (int) $data['zone_id'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'pickup_address'        => $this->pickupAddress,
            'pickup_latitude'       => $this->pickupLatitude,
            'pickup_longitude'      => $this->pickupLongitude,
            'pickup_contact_name'   => $this->pickupContactName,
            'pickup_contact_phone'  => $this->pickupContactPhone,
            'pickup_instructions'   => $this->pickupInstructions,
            'dropoff_address'       => $this->dropoffAddress,
            'dropoff_latitude'      => $this->dropoffLatitude,
            'dropoff_longitude'     => $this->dropoffLongitude,
            'dropoff_contact_name'  => $this->dropoffContactName,
            'dropoff_contact_phone' => $this->dropoffContactPhone,
            'dropoff_instructions'  => $this->dropoffInstructions,
            'package_description'   => $this->packageDescription,
            'package_size'          => $this->packageSize,
            'payment_method'        => $this->paymentMethod,
            'zone_id'               => $this->zoneId,
        ];
    }
}

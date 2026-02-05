# 📱 Flutter Architecture - OUAGA CHAP

> Clean Architecture implementation for the mobile delivery application

---

## 1. Architecture Overview

### 1.1 Clean Architecture Layers

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           PRESENTATION LAYER                                 │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐             │
│  │     Pages       │  │     Widgets     │  │      BLoC       │             │
│  │   (Screens)     │  │   (Components)  │  │ (State Mgmt)    │             │
│  └────────┬────────┘  └────────┬────────┘  └────────┬────────┘             │
│           │                    │                    │                       │
│           └────────────────────┼────────────────────┘                       │
│                                │                                            │
├────────────────────────────────┼────────────────────────────────────────────┤
│                          DOMAIN LAYER                                        │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐             │
│  │    Entities     │  │    Use Cases    │  │  Repositories   │             │
│  │  (Pure Models)  │  │(Business Logic) │  │  (Interfaces)   │             │
│  └─────────────────┘  └────────┬────────┘  └────────┬────────┘             │
│                                │                    │                       │
├────────────────────────────────┼────────────────────┼────────────────────────┤
│                           DATA LAYER                                         │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐             │
│  │     Models      │  │  Data Sources   │  │  Repositories   │             │
│  │  (DTO/JSON)     │  │ (Remote/Local)  │  │ (Implements)    │             │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘             │
│                                │                                            │
└────────────────────────────────┼────────────────────────────────────────────┘
                                 │
                    ┌────────────▼────────────┐
                    │    External Services    │
                    │  (API, Storage, Maps)   │
                    └─────────────────────────┘
```

### 1.2 Key Principles
- **Separation of Concerns**: Each layer has distinct responsibilities
- **Dependency Rule**: Inner layers don't depend on outer layers
- **Testability**: Business logic is easily testable
- **Flexibility**: Easy to swap implementations (e.g., mock → real API)

---

## 2. Project Structure

### 2.1 Complete Folder Structure

```
lib/
├── main.dart                           # App entry point
├── injection_container.dart            # Dependency injection setup
│
├── app/
│   ├── app.dart                        # MaterialApp configuration
│   ├── routes.dart                     # Route definitions
│   └── theme.dart                      # App theme
│
├── core/
│   ├── constants/
│   │   ├── api_constants.dart          # API URLs, endpoints
│   │   ├── app_constants.dart          # App-wide constants
│   │   └── color_constants.dart        # Color palette
│   │
│   ├── errors/
│   │   ├── exceptions.dart             # Custom exceptions
│   │   └── failures.dart               # Failure classes
│   │
│   ├── network/
│   │   ├── api_client.dart             # Dio setup
│   │   ├── network_info.dart           # Connectivity checker
│   │   └── interceptors/
│   │       ├── auth_interceptor.dart   # Token injection
│   │       ├── error_interceptor.dart  # Error handling
│   │       └── retry_interceptor.dart  # Retry logic
│   │
│   ├── usecases/
│   │   └── usecase.dart                # Base UseCase class
│   │
│   └── utils/
│       ├── validators.dart             # Input validators
│       ├── formatters.dart             # Date, currency formatters
│       └── helpers.dart                # Utility functions
│
├── features/
│   ├── auth/
│   │   ├── data/
│   │   │   ├── datasources/
│   │   │   │   ├── auth_remote_datasource.dart
│   │   │   │   └── auth_local_datasource.dart
│   │   │   ├── models/
│   │   │   │   ├── user_model.dart
│   │   │   │   └── otp_response_model.dart
│   │   │   └── repositories/
│   │   │       └── auth_repository_impl.dart
│   │   ├── domain/
│   │   │   ├── entities/
│   │   │   │   └── user.dart
│   │   │   ├── repositories/
│   │   │   │   └── auth_repository.dart
│   │   │   └── usecases/
│   │   │       ├── request_otp.dart
│   │   │       ├── verify_otp.dart
│   │   │       └── logout.dart
│   │   └── presentation/
│   │       ├── bloc/
│   │       │   ├── auth_bloc.dart
│   │       │   ├── auth_event.dart
│   │       │   └── auth_state.dart
│   │       ├── pages/
│   │       │   ├── login_page.dart
│   │       │   ├── otp_verification_page.dart
│   │       │   └── profile_setup_page.dart
│   │       └── widgets/
│   │           ├── phone_input.dart
│   │           └── otp_input.dart
│   │
│   ├── orders/
│   │   ├── data/
│   │   │   ├── datasources/
│   │   │   │   └── order_remote_datasource.dart
│   │   │   ├── models/
│   │   │   │   ├── order_model.dart
│   │   │   │   └── price_estimate_model.dart
│   │   │   └── repositories/
│   │   │       └── order_repository_impl.dart
│   │   ├── domain/
│   │   │   ├── entities/
│   │   │   │   └── order.dart
│   │   │   ├── repositories/
│   │   │   │   └── order_repository.dart
│   │   │   └── usecases/
│   │   │       ├── create_order.dart
│   │   │       ├── get_order.dart
│   │   │       ├── get_orders.dart
│   │   │       ├── cancel_order.dart
│   │   │       └── calculate_price.dart
│   │   └── presentation/
│   │       ├── bloc/
│   │       │   └── order_bloc.dart
│   │       ├── pages/
│   │       │   ├── create_order_page.dart
│   │       │   ├── order_details_page.dart
│   │       │   └── order_history_page.dart
│   │       └── widgets/
│   │           ├── address_input.dart
│   │           ├── order_card.dart
│   │           └── price_summary.dart
│   │
│   ├── tracking/
│   │   ├── data/
│   │   │   ├── datasources/
│   │   │   │   └── tracking_remote_datasource.dart
│   │   │   ├── models/
│   │   │   │   └── tracking_model.dart
│   │   │   └── repositories/
│   │   │       └── tracking_repository_impl.dart
│   │   ├── domain/
│   │   │   ├── entities/
│   │   │   │   └── tracking_info.dart
│   │   │   ├── repositories/
│   │   │   │   └── tracking_repository.dart
│   │   │   └── usecases/
│   │   │       └── get_tracking.dart
│   │   └── presentation/
│   │       ├── bloc/
│   │       │   └── tracking_bloc.dart
│   │       ├── pages/
│   │       │   └── tracking_page.dart
│   │       └── widgets/
│   │           ├── tracking_map.dart
│   │           └── tracking_timeline.dart
│   │
│   ├── payment/
│   │   ├── data/
│   │   ├── domain/
│   │   └── presentation/
│   │
│   ├── courier/                        # Courier-specific features
│   │   ├── data/
│   │   ├── domain/
│   │   └── presentation/
│   │       ├── bloc/
│   │       ├── pages/
│   │       │   ├── courier_home_page.dart
│   │       │   ├── available_orders_page.dart
│   │       │   ├── active_delivery_page.dart
│   │       │   └── earnings_page.dart
│   │       └── widgets/
│   │
│   └── profile/
│       ├── data/
│       ├── domain/
│       └── presentation/
│
└── shared/
    ├── widgets/
    │   ├── app_button.dart
    │   ├── app_text_field.dart
    │   ├── loading_overlay.dart
    │   ├── error_widget.dart
    │   └── empty_state.dart
    │
    ├── services/
    │   ├── location_service.dart
    │   ├── notification_service.dart
    │   └── storage_service.dart
    │
    └── extensions/
        ├── context_extensions.dart
        └── string_extensions.dart
```

---

## 3. Core Layer Implementation

### 3.1 API Client

```dart
// lib/core/network/api_client.dart

import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../constants/api_constants.dart';
import 'interceptors/auth_interceptor.dart';
import 'interceptors/error_interceptor.dart';
import 'interceptors/retry_interceptor.dart';

class ApiClient {
  static Dio? _dio;
  static final _storage = FlutterSecureStorage();

  static Dio get instance {
    _dio ??= _createDio();
    return _dio!;
  }

  static Dio _createDio() {
    final dio = Dio(
      BaseOptions(
        baseUrl: ApiConstants.baseUrl,
        connectTimeout: const Duration(seconds: 30),
        receiveTimeout: const Duration(seconds: 30),
        sendTimeout: const Duration(seconds: 30),
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      ),
    );

    dio.interceptors.addAll([
      AuthInterceptor(_storage),
      RetryInterceptor(dio),
      ErrorInterceptor(),
      LogInterceptor(
        requestBody: true,
        responseBody: true,
        error: true,
      ),
    ]);

    return dio;
  }

  static void reset() {
    _dio = null;
  }
}
```

### 3.2 Auth Interceptor

```dart
// lib/core/network/interceptors/auth_interceptor.dart

import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class AuthInterceptor extends Interceptor {
  final FlutterSecureStorage _storage;

  AuthInterceptor(this._storage);

  @override
  Future<void> onRequest(
    RequestOptions options,
    RequestInterceptorHandler handler,
  ) async {
    // Skip auth for public endpoints
    if (_isPublicEndpoint(options.path)) {
      return handler.next(options);
    }

    final token = await _storage.read(key: 'auth_token');
    if (token != null) {
      options.headers['Authorization'] = 'Bearer $token';
    }

    return handler.next(options);
  }

  @override
  void onError(DioException err, ErrorInterceptorHandler handler) {
    if (err.response?.statusCode == 401) {
      // Token expired, trigger logout
      _storage.delete(key: 'auth_token');
      // Navigate to login (handled by app)
    }
    return handler.next(err);
  }

  bool _isPublicEndpoint(String path) {
    final publicPaths = [
      '/auth/otp/request',
      '/auth/otp/verify',
      '/tracking/',
      '/settings',
      '/health',
    ];
    return publicPaths.any((p) => path.contains(p));
  }
}
```

### 3.3 Error Handling

```dart
// lib/core/errors/failures.dart

import 'package:equatable/equatable.dart';

abstract class Failure extends Equatable {
  final String message;
  final String? code;

  const Failure({required this.message, this.code});

  @override
  List<Object?> get props => [message, code];
}

class ServerFailure extends Failure {
  const ServerFailure({required super.message, super.code});
}

class NetworkFailure extends Failure {
  const NetworkFailure({
    super.message = 'Pas de connexion internet',
    super.code = 'NETWORK_ERROR',
  });
}

class CacheFailure extends Failure {
  const CacheFailure({
    super.message = 'Erreur de cache local',
    super.code = 'CACHE_ERROR',
  });
}

class ValidationFailure extends Failure {
  final Map<String, List<String>>? errors;

  const ValidationFailure({
    required super.message,
    super.code = 'VALIDATION_ERROR',
    this.errors,
  });

  @override
  List<Object?> get props => [message, code, errors];
}
```

```dart
// lib/core/errors/exceptions.dart

class ServerException implements Exception {
  final String message;
  final int? statusCode;
  final String? code;

  ServerException({
    required this.message,
    this.statusCode,
    this.code,
  });
}

class NetworkException implements Exception {
  final String message;

  NetworkException([this.message = 'No internet connection']);
}

class CacheException implements Exception {
  final String message;

  CacheException([this.message = 'Cache error']);
}
```

### 3.4 Base UseCase

```dart
// lib/core/usecases/usecase.dart

import 'package:dartz/dartz.dart';
import '../errors/failures.dart';

abstract class UseCase<Type, Params> {
  Future<Either<Failure, Type>> call(Params params);
}

class NoParams {
  const NoParams();
}
```

---

## 4. Feature Implementation: Auth

### 4.1 Domain Layer

```dart
// lib/features/auth/domain/entities/user.dart

import 'package:equatable/equatable.dart';

class User extends Equatable {
  final int id;
  final String phone;
  final String? name;
  final UserRole role;
  final UserStatus status;
  final double? averageRating;
  final int totalOrders;
  final DateTime createdAt;

  const User({
    required this.id,
    required this.phone,
    this.name,
    required this.role,
    required this.status,
    this.averageRating,
    required this.totalOrders,
    required this.createdAt,
  });

  bool get isCustomer => role == UserRole.customer;
  bool get isCourier => role == UserRole.courier;
  bool get isActive => status == UserStatus.active;

  @override
  List<Object?> get props => [id, phone, name, role, status];
}

enum UserRole { customer, courier, admin }

enum UserStatus { pending, active, suspended, rejected }
```

```dart
// lib/features/auth/domain/repositories/auth_repository.dart

import 'package:dartz/dartz.dart';
import '../../../../core/errors/failures.dart';
import '../entities/user.dart';

abstract class AuthRepository {
  Future<Either<Failure, void>> requestOtp(String phone);
  Future<Either<Failure, AuthResult>> verifyOtp(String phone, String otp);
  Future<Either<Failure, User>> getCurrentUser();
  Future<Either<Failure, User>> updateProfile(String name);
  Future<Either<Failure, void>> logout();
  Future<Either<Failure, void>> updateFcmToken(String token);
}

class AuthResult {
  final String token;
  final User user;
  final bool isNewUser;

  AuthResult({
    required this.token,
    required this.user,
    required this.isNewUser,
  });
}
```

```dart
// lib/features/auth/domain/usecases/request_otp.dart

import 'package:dartz/dartz.dart';
import '../../../../core/errors/failures.dart';
import '../../../../core/usecases/usecase.dart';
import '../repositories/auth_repository.dart';

class RequestOtp implements UseCase<void, RequestOtpParams> {
  final AuthRepository repository;

  RequestOtp(this.repository);

  @override
  Future<Either<Failure, void>> call(RequestOtpParams params) {
    return repository.requestOtp(params.phone);
  }
}

class RequestOtpParams {
  final String phone;

  RequestOtpParams({required this.phone});
}
```

```dart
// lib/features/auth/domain/usecases/verify_otp.dart

import 'package:dartz/dartz.dart';
import '../../../../core/errors/failures.dart';
import '../../../../core/usecases/usecase.dart';
import '../repositories/auth_repository.dart';

class VerifyOtp implements UseCase<AuthResult, VerifyOtpParams> {
  final AuthRepository repository;

  VerifyOtp(this.repository);

  @override
  Future<Either<Failure, AuthResult>> call(VerifyOtpParams params) {
    return repository.verifyOtp(params.phone, params.otp);
  }
}

class VerifyOtpParams {
  final String phone;
  final String otp;

  VerifyOtpParams({required this.phone, required this.otp});
}
```

### 4.2 Data Layer

```dart
// lib/features/auth/data/models/user_model.dart

import '../../domain/entities/user.dart';

class UserModel extends User {
  const UserModel({
    required super.id,
    required super.phone,
    super.name,
    required super.role,
    required super.status,
    super.averageRating,
    required super.totalOrders,
    required super.createdAt,
  });

  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: json['id'],
      phone: json['phone'],
      name: json['name'],
      role: UserRole.values.firstWhere(
        (e) => e.name == json['role'],
        orElse: () => UserRole.customer,
      ),
      status: UserStatus.values.firstWhere(
        (e) => e.name == json['status'],
        orElse: () => UserStatus.pending,
      ),
      averageRating: (json['average_rating'] as num?)?.toDouble(),
      totalOrders: json['total_orders'] ?? 0,
      createdAt: DateTime.parse(json['created_at']),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'phone': phone,
      'name': name,
      'role': role.name,
      'status': status.name,
      'average_rating': averageRating,
      'total_orders': totalOrders,
      'created_at': createdAt.toIso8601String(),
    };
  }
}
```

```dart
// lib/features/auth/data/datasources/auth_remote_datasource.dart

import 'package:dio/dio.dart';
import '../models/user_model.dart';

abstract class AuthRemoteDataSource {
  Future<void> requestOtp(String phone);
  Future<AuthResultModel> verifyOtp(String phone, String otp, String? fcmToken);
  Future<UserModel> getCurrentUser();
  Future<UserModel> updateProfile(String name);
  Future<void> logout();
  Future<void> updateFcmToken(String token);
}

class AuthRemoteDataSourceImpl implements AuthRemoteDataSource {
  final Dio _dio;

  AuthRemoteDataSourceImpl(this._dio);

  @override
  Future<void> requestOtp(String phone) async {
    await _dio.post('/auth/otp/request', data: {'phone': phone});
  }

  @override
  Future<AuthResultModel> verifyOtp(
    String phone,
    String otp,
    String? fcmToken,
  ) async {
    final response = await _dio.post('/auth/otp/verify', data: {
      'phone': phone,
      'otp': otp,
      if (fcmToken != null) 'fcm_token': fcmToken,
    });

    return AuthResultModel.fromJson(response.data['data']);
  }

  @override
  Future<UserModel> getCurrentUser() async {
    final response = await _dio.get('/auth/me');
    return UserModel.fromJson(response.data['data']);
  }

  @override
  Future<UserModel> updateProfile(String name) async {
    final response = await _dio.put('/profile', data: {'name': name});
    return UserModel.fromJson(response.data['data']);
  }

  @override
  Future<void> logout() async {
    await _dio.post('/auth/logout');
  }

  @override
  Future<void> updateFcmToken(String token) async {
    await _dio.put('/notifications/token', data: {'fcm_token': token});
  }
}

class AuthResultModel {
  final String token;
  final UserModel user;
  final bool isNewUser;

  AuthResultModel({
    required this.token,
    required this.user,
    required this.isNewUser,
  });

  factory AuthResultModel.fromJson(Map<String, dynamic> json) {
    return AuthResultModel(
      token: json['token'],
      user: UserModel.fromJson(json['user']),
      isNewUser: json['is_new_user'] ?? false,
    );
  }
}
```

```dart
// lib/features/auth/data/repositories/auth_repository_impl.dart

import 'package:dartz/dartz.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../../../../core/errors/exceptions.dart';
import '../../../../core/errors/failures.dart';
import '../../../../core/network/network_info.dart';
import '../../domain/entities/user.dart';
import '../../domain/repositories/auth_repository.dart';
import '../datasources/auth_local_datasource.dart';
import '../datasources/auth_remote_datasource.dart';

class AuthRepositoryImpl implements AuthRepository {
  final AuthRemoteDataSource remoteDataSource;
  final AuthLocalDataSource localDataSource;
  final NetworkInfo networkInfo;
  final FlutterSecureStorage secureStorage;

  AuthRepositoryImpl({
    required this.remoteDataSource,
    required this.localDataSource,
    required this.networkInfo,
    required this.secureStorage,
  });

  @override
  Future<Either<Failure, void>> requestOtp(String phone) async {
    if (!await networkInfo.isConnected) {
      return const Left(NetworkFailure());
    }

    try {
      await remoteDataSource.requestOtp(phone);
      return const Right(null);
    } on ServerException catch (e) {
      return Left(ServerFailure(message: e.message, code: e.code));
    }
  }

  @override
  Future<Either<Failure, AuthResult>> verifyOtp(
    String phone,
    String otp,
  ) async {
    if (!await networkInfo.isConnected) {
      return const Left(NetworkFailure());
    }

    try {
      final fcmToken = await localDataSource.getFcmToken();
      final result = await remoteDataSource.verifyOtp(phone, otp, fcmToken);

      // Save token
      await secureStorage.write(key: 'auth_token', value: result.token);

      // Cache user
      await localDataSource.cacheUser(result.user);

      return Right(AuthResult(
        token: result.token,
        user: result.user,
        isNewUser: result.isNewUser,
      ));
    } on ServerException catch (e) {
      return Left(ServerFailure(message: e.message, code: e.code));
    }
  }

  @override
  Future<Either<Failure, User>> getCurrentUser() async {
    try {
      if (await networkInfo.isConnected) {
        final user = await remoteDataSource.getCurrentUser();
        await localDataSource.cacheUser(user);
        return Right(user);
      } else {
        final user = await localDataSource.getCachedUser();
        return Right(user);
      }
    } on ServerException catch (e) {
      return Left(ServerFailure(message: e.message));
    } on CacheException catch (e) {
      return Left(CacheFailure(message: e.message));
    }
  }

  @override
  Future<Either<Failure, User>> updateProfile(String name) async {
    if (!await networkInfo.isConnected) {
      return const Left(NetworkFailure());
    }

    try {
      final user = await remoteDataSource.updateProfile(name);
      await localDataSource.cacheUser(user);
      return Right(user);
    } on ServerException catch (e) {
      return Left(ServerFailure(message: e.message));
    }
  }

  @override
  Future<Either<Failure, void>> logout() async {
    try {
      if (await networkInfo.isConnected) {
        await remoteDataSource.logout();
      }
      await secureStorage.delete(key: 'auth_token');
      await localDataSource.clearCache();
      return const Right(null);
    } catch (e) {
      return Left(ServerFailure(message: e.toString()));
    }
  }

  @override
  Future<Either<Failure, void>> updateFcmToken(String token) async {
    try {
      await localDataSource.saveFcmToken(token);
      if (await networkInfo.isConnected) {
        await remoteDataSource.updateFcmToken(token);
      }
      return const Right(null);
    } catch (e) {
      return Left(ServerFailure(message: e.toString()));
    }
  }
}
```

### 4.3 Presentation Layer (BLoC)

```dart
// lib/features/auth/presentation/bloc/auth_bloc.dart

import 'package:flutter_bloc/flutter_bloc.dart';
import '../../domain/usecases/request_otp.dart';
import '../../domain/usecases/verify_otp.dart';
import '../../domain/usecases/logout.dart';
import '../../domain/usecases/get_current_user.dart';
import 'auth_event.dart';
import 'auth_state.dart';

class AuthBloc extends Bloc<AuthEvent, AuthState> {
  final RequestOtp requestOtp;
  final VerifyOtp verifyOtp;
  final Logout logout;
  final GetCurrentUser getCurrentUser;

  AuthBloc({
    required this.requestOtp,
    required this.verifyOtp,
    required this.logout,
    required this.getCurrentUser,
  }) : super(AuthInitial()) {
    on<CheckAuthStatusEvent>(_onCheckAuthStatus);
    on<RequestOtpEvent>(_onRequestOtp);
    on<VerifyOtpEvent>(_onVerifyOtp);
    on<LogoutEvent>(_onLogout);
  }

  Future<void> _onCheckAuthStatus(
    CheckAuthStatusEvent event,
    Emitter<AuthState> emit,
  ) async {
    emit(AuthLoading());

    final result = await getCurrentUser(const NoParams());

    result.fold(
      (failure) => emit(AuthUnauthenticated()),
      (user) => emit(AuthAuthenticated(user)),
    );
  }

  Future<void> _onRequestOtp(
    RequestOtpEvent event,
    Emitter<AuthState> emit,
  ) async {
    emit(AuthLoading());

    final result = await requestOtp(RequestOtpParams(phone: event.phone));

    result.fold(
      (failure) => emit(AuthError(failure.message)),
      (_) => emit(OtpSent(event.phone)),
    );
  }

  Future<void> _onVerifyOtp(
    VerifyOtpEvent event,
    Emitter<AuthState> emit,
  ) async {
    emit(AuthLoading());

    final result = await verifyOtp(VerifyOtpParams(
      phone: event.phone,
      otp: event.otp,
    ));

    result.fold(
      (failure) => emit(AuthError(failure.message)),
      (authResult) {
        if (authResult.isNewUser || authResult.user.name == null) {
          emit(AuthNeedsProfile(authResult.user));
        } else {
          emit(AuthAuthenticated(authResult.user));
        }
      },
    );
  }

  Future<void> _onLogout(
    LogoutEvent event,
    Emitter<AuthState> emit,
  ) async {
    emit(AuthLoading());
    await logout(const NoParams());
    emit(AuthUnauthenticated());
  }
}
```

```dart
// lib/features/auth/presentation/bloc/auth_event.dart

import 'package:equatable/equatable.dart';

abstract class AuthEvent extends Equatable {
  @override
  List<Object?> get props => [];
}

class CheckAuthStatusEvent extends AuthEvent {}

class RequestOtpEvent extends AuthEvent {
  final String phone;

  RequestOtpEvent({required this.phone});

  @override
  List<Object?> get props => [phone];
}

class VerifyOtpEvent extends AuthEvent {
  final String phone;
  final String otp;

  VerifyOtpEvent({required this.phone, required this.otp});

  @override
  List<Object?> get props => [phone, otp];
}

class LogoutEvent extends AuthEvent {}
```

```dart
// lib/features/auth/presentation/bloc/auth_state.dart

import 'package:equatable/equatable.dart';
import '../../domain/entities/user.dart';

abstract class AuthState extends Equatable {
  @override
  List<Object?> get props => [];
}

class AuthInitial extends AuthState {}

class AuthLoading extends AuthState {}

class AuthUnauthenticated extends AuthState {}

class OtpSent extends AuthState {
  final String phone;

  OtpSent(this.phone);

  @override
  List<Object?> get props => [phone];
}

class AuthAuthenticated extends AuthState {
  final User user;

  AuthAuthenticated(this.user);

  @override
  List<Object?> get props => [user];
}

class AuthNeedsProfile extends AuthState {
  final User user;

  AuthNeedsProfile(this.user);

  @override
  List<Object?> get props => [user];
}

class AuthError extends AuthState {
  final String message;

  AuthError(this.message);

  @override
  List<Object?> get props => [message];
}
```

---

## 5. Dependency Injection

```dart
// lib/injection_container.dart

import 'package:dio/dio.dart';
import 'package:get_it/get_it.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'core/network/api_client.dart';
import 'core/network/network_info.dart';
import 'features/auth/data/datasources/auth_remote_datasource.dart';
import 'features/auth/data/datasources/auth_local_datasource.dart';
import 'features/auth/data/repositories/auth_repository_impl.dart';
import 'features/auth/domain/repositories/auth_repository.dart';
import 'features/auth/domain/usecases/request_otp.dart';
import 'features/auth/domain/usecases/verify_otp.dart';
import 'features/auth/domain/usecases/logout.dart';
import 'features/auth/domain/usecases/get_current_user.dart';
import 'features/auth/presentation/bloc/auth_bloc.dart';

final sl = GetIt.instance;

Future<void> init() async {
  //! External
  final sharedPreferences = await SharedPreferences.getInstance();
  sl.registerLazySingleton(() => sharedPreferences);
  sl.registerLazySingleton(() => const FlutterSecureStorage());
  sl.registerLazySingleton<Dio>(() => ApiClient.instance);

  //! Core
  sl.registerLazySingleton<NetworkInfo>(() => NetworkInfoImpl());

  //! Features - Auth
  // Bloc
  sl.registerFactory(
    () => AuthBloc(
      requestOtp: sl(),
      verifyOtp: sl(),
      logout: sl(),
      getCurrentUser: sl(),
    ),
  );

  // Use cases
  sl.registerLazySingleton(() => RequestOtp(sl()));
  sl.registerLazySingleton(() => VerifyOtp(sl()));
  sl.registerLazySingleton(() => Logout(sl()));
  sl.registerLazySingleton(() => GetCurrentUser(sl()));

  // Repository
  sl.registerLazySingleton<AuthRepository>(
    () => AuthRepositoryImpl(
      remoteDataSource: sl(),
      localDataSource: sl(),
      networkInfo: sl(),
      secureStorage: sl(),
    ),
  );

  // Data sources
  sl.registerLazySingleton<AuthRemoteDataSource>(
    () => AuthRemoteDataSourceImpl(sl()),
  );
  sl.registerLazySingleton<AuthLocalDataSource>(
    () => AuthLocalDataSourceImpl(sl()),
  );

  //! Features - Orders
  // ... Similar pattern for orders feature

  //! Features - Tracking
  // ... Similar pattern for tracking feature
}
```

---

## 6. App Configuration

### 6.1 Main Entry Point

```dart
// lib/main.dart

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:firebase_core/firebase_core.dart';

import 'injection_container.dart' as di;
import 'app/app.dart';
import 'shared/services/notification_service.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Initialize Firebase
  await Firebase.initializeApp();

  // Initialize dependency injection
  await di.init();

  // Initialize notifications
  await di.sl<NotificationService>().initialize();

  runApp(const OuagaChapApp());
}
```

### 6.2 App Widget

```dart
// lib/app/app.dart

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_localizations/flutter_localizations.dart';

import '../injection_container.dart';
import '../features/auth/presentation/bloc/auth_bloc.dart';
import 'routes.dart';
import 'theme.dart';

class OuagaChapApp extends StatelessWidget {
  const OuagaChapApp({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return MultiBlocProvider(
      providers: [
        BlocProvider(
          create: (_) => sl<AuthBloc>()..add(CheckAuthStatusEvent()),
        ),
        // Add other global blocs here
      ],
      child: MaterialApp(
        title: 'OUAGA CHAP',
        debugShowCheckedModeBanner: false,
        theme: AppTheme.lightTheme,
        darkTheme: AppTheme.darkTheme,
        themeMode: ThemeMode.light,
        localizationsDelegates: const [
          GlobalMaterialLocalizations.delegate,
          GlobalWidgetsLocalizations.delegate,
          GlobalCupertinoLocalizations.delegate,
        ],
        supportedLocales: const [
          Locale('fr', 'FR'), // French
          Locale('en', 'US'), // English (fallback)
        ],
        locale: const Locale('fr', 'FR'),
        onGenerateRoute: AppRoutes.onGenerateRoute,
        initialRoute: AppRoutes.splash,
      ),
    );
  }
}
```

### 6.3 Theme

```dart
// lib/app/theme.dart

import 'package:flutter/material.dart';

class AppTheme {
  static const Color primaryColor = Color(0xFFFF6B00);
  static const Color secondaryColor = Color(0xFF1A1A2E);
  static const Color successColor = Color(0xFF4CAF50);
  static const Color warningColor = Color(0xFFFFC107);
  static const Color errorColor = Color(0xFFF44336);
  static const Color backgroundColor = Color(0xFFF5F5F5);

  static ThemeData get lightTheme {
    return ThemeData(
      useMaterial3: true,
      colorScheme: ColorScheme.fromSeed(
        seedColor: primaryColor,
        primary: primaryColor,
        secondary: secondaryColor,
        error: errorColor,
        background: backgroundColor,
      ),
      appBarTheme: const AppBarTheme(
        backgroundColor: Colors.white,
        foregroundColor: secondaryColor,
        elevation: 0,
        centerTitle: true,
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: primaryColor,
          foregroundColor: Colors.white,
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(8),
          ),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: Colors.white,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(8),
          borderSide: const BorderSide(color: Colors.grey),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(8),
          borderSide: BorderSide(color: Colors.grey.shade300),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(8),
          borderSide: const BorderSide(color: primaryColor, width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(8),
          borderSide: const BorderSide(color: errorColor),
        ),
      ),
      cardTheme: CardTheme(
        elevation: 2,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12),
        ),
      ),
    );
  }

  static ThemeData get darkTheme {
    return lightTheme.copyWith(
      brightness: Brightness.dark,
      // Customize for dark mode
    );
  }
}
```

### 6.4 Routes

```dart
// lib/app/routes.dart

import 'package:flutter/material.dart';

import '../features/auth/presentation/pages/login_page.dart';
import '../features/auth/presentation/pages/otp_verification_page.dart';
import '../features/auth/presentation/pages/profile_setup_page.dart';
import '../features/orders/presentation/pages/customer_home_page.dart';
import '../features/orders/presentation/pages/create_order_page.dart';
import '../features/orders/presentation/pages/order_details_page.dart';
import '../features/tracking/presentation/pages/tracking_page.dart';
import '../features/courier/presentation/pages/courier_home_page.dart';
import '../shared/pages/splash_page.dart';

class AppRoutes {
  static const String splash = '/';
  static const String login = '/login';
  static const String otpVerification = '/otp-verification';
  static const String profileSetup = '/profile-setup';
  static const String customerHome = '/customer/home';
  static const String createOrder = '/customer/create-order';
  static const String orderDetails = '/orders/:uuid';
  static const String tracking = '/tracking/:uuid';
  static const String courierHome = '/courier/home';
  static const String courierDelivery = '/courier/delivery';
  static const String courierEarnings = '/courier/earnings';

  static Route<dynamic> onGenerateRoute(RouteSettings settings) {
    switch (settings.name) {
      case splash:
        return MaterialPageRoute(builder: (_) => const SplashPage());

      case login:
        return MaterialPageRoute(builder: (_) => const LoginPage());

      case otpVerification:
        final phone = settings.arguments as String;
        return MaterialPageRoute(
          builder: (_) => OtpVerificationPage(phone: phone),
        );

      case profileSetup:
        return MaterialPageRoute(builder: (_) => const ProfileSetupPage());

      case customerHome:
        return MaterialPageRoute(builder: (_) => const CustomerHomePage());

      case createOrder:
        return MaterialPageRoute(builder: (_) => const CreateOrderPage());

      case courierHome:
        return MaterialPageRoute(builder: (_) => const CourierHomePage());

      default:
        // Handle dynamic routes
        if (settings.name?.startsWith('/orders/') ?? false) {
          final uuid = settings.name!.split('/').last;
          return MaterialPageRoute(
            builder: (_) => OrderDetailsPage(uuid: uuid),
          );
        }

        if (settings.name?.startsWith('/tracking/') ?? false) {
          final uuid = settings.name!.split('/').last;
          return MaterialPageRoute(
            builder: (_) => TrackingPage(uuid: uuid),
          );
        }

        return MaterialPageRoute(
          builder: (_) => Scaffold(
            body: Center(
              child: Text('Route not found: ${settings.name}'),
            ),
          ),
        );
    }
  }
}
```

---

## 7. Shared Services

### 7.1 Location Service

```dart
// lib/shared/services/location_service.dart

import 'package:geolocator/geolocator.dart';

class LocationService {
  Future<bool> requestPermission() async {
    bool serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) {
      return false;
    }

    LocationPermission permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
      if (permission == LocationPermission.denied) {
        return false;
      }
    }

    if (permission == LocationPermission.deniedForever) {
      return false;
    }

    return true;
  }

  Future<Position?> getCurrentPosition() async {
    try {
      return await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
      );
    } catch (e) {
      return null;
    }
  }

  Stream<Position> getPositionStream() {
    return Geolocator.getPositionStream(
      locationSettings: const LocationSettings(
        accuracy: LocationAccuracy.high,
        distanceFilter: 10, // meters
      ),
    );
  }

  Future<double> calculateDistance(
    double startLat,
    double startLng,
    double endLat,
    double endLng,
  ) async {
    return Geolocator.distanceBetween(startLat, startLng, endLat, endLng);
  }
}
```

### 7.2 Notification Service

```dart
// lib/shared/services/notification_service.dart

import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

class NotificationService {
  final FirebaseMessaging _messaging = FirebaseMessaging.instance;
  final FlutterLocalNotificationsPlugin _localNotifications =
      FlutterLocalNotificationsPlugin();

  Future<void> initialize() async {
    // Request permission
    await _messaging.requestPermission(
      alert: true,
      badge: true,
      sound: true,
    );

    // Initialize local notifications
    const initSettings = InitializationSettings(
      android: AndroidInitializationSettings('@mipmap/ic_launcher'),
      iOS: DarwinInitializationSettings(),
    );
    await _localNotifications.initialize(initSettings);

    // Handle foreground messages
    FirebaseMessaging.onMessage.listen(_handleForegroundMessage);

    // Handle background messages
    FirebaseMessaging.onBackgroundMessage(_handleBackgroundMessage);

    // Handle notification taps
    FirebaseMessaging.onMessageOpenedApp.listen(_handleMessageOpenedApp);
  }

  Future<String?> getToken() async {
    return await _messaging.getToken();
  }

  void _handleForegroundMessage(RemoteMessage message) {
    _showLocalNotification(message);
  }

  Future<void> _showLocalNotification(RemoteMessage message) async {
    const androidDetails = AndroidNotificationDetails(
      'ouagachap_channel',
      'OUAGA CHAP',
      channelDescription: 'Notifications OUAGA CHAP',
      importance: Importance.high,
      priority: Priority.high,
    );

    const notificationDetails = NotificationDetails(
      android: androidDetails,
      iOS: DarwinNotificationDetails(),
    );

    await _localNotifications.show(
      message.hashCode,
      message.notification?.title,
      message.notification?.body,
      notificationDetails,
      payload: message.data.toString(),
    );
  }

  void _handleMessageOpenedApp(RemoteMessage message) {
    // Navigate based on notification data
    final data = message.data;
    if (data['type'] == 'order_update') {
      // Navigate to order details
    }
  }
}

@pragma('vm:entry-point')
Future<void> _handleBackgroundMessage(RemoteMessage message) async {
  // Handle background message
}
```

---

## 8. Testing Structure

```
test/
├── core/
│   ├── network/
│   │   └── api_client_test.dart
│   └── utils/
│       └── validators_test.dart
│
├── features/
│   ├── auth/
│   │   ├── data/
│   │   │   ├── datasources/
│   │   │   │   └── auth_remote_datasource_test.dart
│   │   │   └── repositories/
│   │   │       └── auth_repository_impl_test.dart
│   │   ├── domain/
│   │   │   └── usecases/
│   │   │       ├── request_otp_test.dart
│   │   │       └── verify_otp_test.dart
│   │   └── presentation/
│   │       └── bloc/
│   │           └── auth_bloc_test.dart
│   │
│   └── orders/
│       └── ...
│
├── fixtures/
│   └── fixture_reader.dart
│
└── helpers/
    └── test_helper.dart
```

---

*Document technique - Dernière mise à jour: Janvier 2026*

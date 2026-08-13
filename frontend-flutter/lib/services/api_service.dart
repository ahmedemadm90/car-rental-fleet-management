import 'dart:convert';

import 'package:http/http.dart' as http;

import '../models/models.dart';

class ApiException implements Exception {
  ApiException(this.message, {this.statusCode});
  final String message;
  final int? statusCode;

  @override
  String toString() => message;
}

class ApiService {
  ApiService({http.Client? client}) : _client = client ?? http.Client();

  static const String baseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://10.0.2.2:8000/api/v1',
  );

  final http.Client _client;
  String? _token;

  void setToken(String? token) => _token = token;

  Map<String, String> get _headers => {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        if (_token != null) 'Authorization': 'Bearer $_token',
      };

  Future<Map<String, dynamic>> _request(
    String method,
    String path, {
    Map<String, dynamic>? body,
    Map<String, String>? query,
  }) async {
    final uri = Uri.parse('$baseUrl$path').replace(queryParameters: query);
    late final http.Response response;
    try {
      switch (method) {
        case 'GET':
          response = await _client.get(uri, headers: _headers);
        case 'POST':
          response = await _client.post(uri, headers: _headers, body: jsonEncode(body));
        case 'PATCH':
          response = await _client.patch(uri, headers: _headers, body: jsonEncode(body));
        case 'DELETE':
          response = await _client.delete(uri, headers: _headers);
        default:
          throw ApiException('Unsupported request method: $method');
      }
    } on Exception {
      throw ApiException('تعذر الاتصال بالخادم. تأكد من تشغيل Laravel وإعداد عنوان API الصحيح.');
    }

    if (response.statusCode == 204) return <String, dynamic>{};
    final decoded = response.body.isEmpty ? <String, dynamic>{} : jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode < 200 || response.statusCode >= 300) {
      final errors = decoded['errors'];
      final detail = errors is Map && errors.isNotEmpty ? (errors.values.first as List).first : null;
      throw ApiException(detail?.toString() ?? decoded['message']?.toString() ?? 'حدث خطأ غير متوقع.', statusCode: response.statusCode);
    }
    return decoded;
  }

  Future<List<Car>> searchCars({String? city}) async {
    final json = await _request('GET', '/cars', query: city == null || city.isEmpty ? null : {'city': city});
    final rows = (json['data'] as Map<String, dynamic>)['data'] as List<dynamic>;
    return rows.map((e) => Car.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<Car> getCar(int carId) async {
    final json = await _request('GET', '/cars/$carId');
    return Car.fromJson(json['data'] as Map<String, dynamic>);
  }

  Future<({AppUser user, String token})> login(String email, String password) async {
    final json = await _request('POST', '/auth/login', body: {'email': email, 'password': password});
    final data = json['data'] as Map<String, dynamic>;
    return (user: AppUser.fromJson(data['user'] as Map<String, dynamic>), token: data['token'] as String);
  }

  Future<({AppUser user, String token})> register({
    required String name,
    required String email,
    required String password,
    required String role,
    String? phone,
  }) async {
    final json = await _request('POST', '/auth/register', body: {
      'name': name,
      'email': email,
      'phone': phone,
      'password': password,
      'password_confirmation': password,
      'role': role,
    });
    final data = json['data'] as Map<String, dynamic>;
    return (user: AppUser.fromJson(data['user'] as Map<String, dynamic>), token: data['token'] as String);
  }

  Future<List<Booking>> myBookings() async {
    final json = await _request('GET', '/bookings/me');
    final rows = (json['data'] as Map<String, dynamic>)['data'] as List<dynamic>;
    return rows.map((e) => Booking.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<void> createBooking({
    required int carId,
    required String rentalType,
    required DateTime startDate,
    required DateTime endDate,
    required String pickupLocation,
  }) async {
    await _request('POST', '/bookings', body: {
      'car_id': carId,
      'rental_type': rentalType,
      'start_date': startDate.toIso8601String().split('T').first,
      'end_date': endDate.toIso8601String().split('T').first,
      'pickup_location': pickupLocation,
    });
  }

  Future<PaymentSession> createCheckout(int bookingId) async {
    final json = await _request('POST', '/bookings/$bookingId/payment-checkout');
    return PaymentSession.fromJson(json['data'] as Map<String, dynamic>);
  }

  Future<PaymentSession> paymentStatus(int paymentId) async {
    final json = await _request('GET', '/payments/$paymentId');
    return PaymentSession.fromJson(json['data'] as Map<String, dynamic>);
  }

  Future<({List<AppNotificationItem> items, int unreadCount})> notifications() async {
    final json = await _request('GET', '/notifications');
    final data = json['data'] as Map<String, dynamic>;
    final page = data['items'] as Map<String, dynamic>;
    final items = (page['data'] as List<dynamic>).map((e) => AppNotificationItem.fromJson(e as Map<String, dynamic>)).toList();
    return (items: items, unreadCount: data['unread_count'] as int);
  }

  Future<void> markNotificationRead(String notificationId) async {
    await _request('PATCH', '/notifications/$notificationId/read');
  }

  Future<void> registerPushToken({required String token, required String platform}) async {
    await _request('POST', '/devices/push-token', body: {'token': token, 'platform': platform});
  }

  Future<DashboardSummary> dashboard() async {
    final json = await _request('GET', '/owner/dashboard');
    return DashboardSummary.fromJson(json['data'] as Map<String, dynamic>);
  }

  Future<List<RentalShop>> ownerShops() async {
    final json = await _request('GET', '/owner/shops');
    return (json['data'] as List<dynamic>).map((e) => RentalShop.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<RentalShop> createShop(Map<String, dynamic> input) async {
    final json = await _request('POST', '/owner/shops', body: input);
    return RentalShop.fromJson(json['data'] as Map<String, dynamic>);
  }

  Future<List<Car>> ownerCars(int shopId) async {
    final json = await _request('GET', '/owner/shops/$shopId/cars');
    final rows = (json['data'] as Map<String, dynamic>)['data'] as List<dynamic>;
    return rows.map((e) => Car.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<Car> createCar(int shopId, Map<String, dynamic> input) async {
    final json = await _request('POST', '/owner/shops/$shopId/cars', body: input);
    return Car.fromJson(json['data'] as Map<String, dynamic>);
  }

  Future<void> addExpense(int carId, Map<String, dynamic> input) => _request('POST', '/owner/cars/$carId/expenses', body: input);

  Future<void> addMaintenance(int carId, Map<String, dynamic> input) => _request('POST', '/owner/cars/$carId/maintenance', body: input);
}

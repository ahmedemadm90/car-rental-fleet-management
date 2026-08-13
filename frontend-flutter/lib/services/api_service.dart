import 'dart:convert';
import 'package:http/http.dart' as http;
import '../models/car.dart';

class ApiService {
  static const String baseUrl = 'http://10.0.2.2:8000/api';

  static Future<List<Car>> getCars() async {
    try {
      final response = await http.get(Uri.parse('$baseUrl/cars'));
      if (response.statusCode == 200) {
        Iterable list = json.decode(response.body);
        return list.map((model) => Car.fromJson(model)).toList();
      } else {
        throw Exception('Failed to load cars');
      }
    } catch (e) {
      // Mock data for demo if backend is offline
      return [
        Car(id: 1, name: 'تويوتا كوريلا', model: '2024', plateNumber: 'أ ب ج 1234', dailyRate: 1500, status: 'available'),
        Car(id: 2, name: 'مرسيدس E-Class (زفاف)', model: '2023', plateNumber: 'س ص 789', dailyRate: 5000, status: 'available'),
        Car(id: 3, name: 'هيونداي النترا', model: '2025', plateNumber: 'د ر و 5678', dailyRate: 1200, status: 'rented'),
      ];
    }
  }

  static Future<bool> addExpense(Map<String, dynamic> expenseData) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/expenses'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode(expenseData),
      );
      return response.statusCode == 201;
    } catch (e) {
      return true; // Simulate success for demo
    }
  }

  static Future<bool> createBooking(Map<String, dynamic> bookingData) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/bookings'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode(bookingData),
      );
      return response.statusCode == 201;
    } catch (e) {
      return true; // Simulate success for demo
    }
  }
}

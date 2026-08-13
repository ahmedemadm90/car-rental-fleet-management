import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../models/models.dart';
import 'api_service.dart';

class SessionController extends ChangeNotifier {
  SessionController(this.apiService);

  final ApiService apiService;
  AppUser? user;
  bool loading = true;

  bool get isAuthenticated => user != null;

  Future<void> restore() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('auth_token');
    final rawUser = prefs.getString('auth_user');
    if (token != null && rawUser != null) {
      apiService.setToken(token);
      user = AppUser.fromJson(jsonDecode(rawUser) as Map<String, dynamic>);
    }
    loading = false;
    notifyListeners();
  }

  Future<void> login(String email, String password) async {
    final result = await apiService.login(email, password);
    await _save(result.user, result.token);
  }

  Future<void> register({
    required String name,
    required String email,
    required String password,
    required String role,
    String? phone,
  }) async {
    final result = await apiService.register(name: name, email: email, password: password, role: role, phone: phone);
    await _save(result.user, result.token);
  }

  Future<void> _save(AppUser newUser, String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('auth_token', token);
    await prefs.setString('auth_user', jsonEncode({
      'id': newUser.id,
      'name': newUser.name,
      'email': newUser.email,
      'role': newUser.role,
      'phone': newUser.phone,
    }));
    apiService.setToken(token);
    user = newUser;
    notifyListeners();
  }

  Future<void> logout() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('auth_token');
    await prefs.remove('auth_user');
    apiService.setToken(null);
    user = null;
    notifyListeners();
  }
}

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';

import 'api_service.dart';

class PushNotificationService {
  PushNotificationService(this._api);

  static const bool enabled = bool.fromEnvironment('FIREBASE_ENABLED', defaultValue: false);
  final ApiService _api;
  final ValueNotifier<int> foregroundMessageCount = ValueNotifier<int>(0);

  Future<void> initialise() async {
    if (!enabled) return;

    try {
      await Firebase.initializeApp();
      final messaging = FirebaseMessaging.instance;
      final permission = await messaging.requestPermission(alert: true, badge: true, sound: true);
      if (permission.authorizationStatus != AuthorizationStatus.authorized && permission.authorizationStatus != AuthorizationStatus.provisional) {
        return;
      }

      final token = await messaging.getToken();
      if (token != null) await _register(token);
      messaging.onTokenRefresh.listen(_register);
      FirebaseMessaging.onMessage.listen((_) => foregroundMessageCount.value++);
    } on FirebaseException {
      // Firebase is intentionally optional until the project configuration is supplied.
    }
  }

  Future<void> _register(String token) => _api.registerPushToken(token: token, platform: _platform);

  String get _platform {
    if (kIsWeb) return 'web';
    return switch (defaultTargetPlatform) {
      TargetPlatform.android => 'android',
      TargetPlatform.iOS => 'ios',
      _ => 'unknown',
    };
  }
}

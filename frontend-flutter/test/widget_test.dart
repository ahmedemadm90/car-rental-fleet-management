import 'package:car_rental_app/main.dart';
import 'package:car_rental_app/services/api_service.dart';
import 'package:car_rental_app/services/session_controller.dart';
import 'package:car_rental_app/services/push_notification_service.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  testWidgets('shows the Arabic login screen when no session is stored', (tester) async {
    SharedPreferences.setMockInitialValues({});
    final api = ApiService();
    final session = SessionController(api);
    await session.restore();

    await tester.pumpWidget(CarRentalApp(api: api, session: session, pushNotifications: PushNotificationService(api)));

    expect(find.text('رحلتي للسيارات'), findsOneWidget);
    expect(find.text('تسجيل الدخول'), findsOneWidget);
  });
}

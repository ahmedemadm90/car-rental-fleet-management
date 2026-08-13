import 'package:car_rental_app/models/models.dart';
import 'package:car_rental_app/services/api_service.dart';
import 'package:car_rental_app/theme/app_theme.dart';
import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';

class CheckoutScreen extends StatefulWidget {
  const CheckoutScreen({super.key, required this.api, required this.session});

  final ApiService api;
  final PaymentSession session;

  @override
  State<CheckoutScreen> createState() => _CheckoutScreenState();
}

class _CheckoutScreenState extends State<CheckoutScreen> {
  late final WebViewController _controller;
  bool _verifying = false;

  @override
  void initState() {
    super.initState();
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setNavigationDelegate(
        NavigationDelegate(
          onNavigationRequest: (request) {
            if (request.url.contains('/payments/paymob/redirect')) {
              _verifyPayment();
              return NavigationDecision.prevent;
            }
            return NavigationDecision.navigate;
          },
        ),
      )
      ..loadRequest(Uri.parse(widget.session.checkoutUrl!));
  }

  Future<void> _verifyPayment() async {
    if (_verifying) return;
    setState(() => _verifying = true);
    try {
      await Future<void>.delayed(const Duration(seconds: 2));
      final payment = await widget.api.paymentStatus(widget.session.id);
      if (!mounted) return;
      if (payment.status == 'paid') {
        Navigator.of(context).pop(true);
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('لم يتأكد الدفع بعد. ستتم مراجعة الحالة تلقائياً من بوابة الدفع.')));
      Navigator.of(context).pop(false);
    } on ApiException catch (error) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(error.message), backgroundColor: Colors.red));
    } finally {
      if (mounted) setState(() => _verifying = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        appBar: AppBar(title: const Text('دفع آمن'), actions: [TextButton(onPressed: () => Navigator.of(context).pop(false), child: const Text('إلغاء', style: TextStyle(color: Colors.white)))]),
        body: Stack(
          children: [
            WebViewWidget(controller: _controller),
            if (_verifying)
              Container(
                color: Colors.white.withValues(alpha: .94),
                child: const Center(
                  child: Column(mainAxisSize: MainAxisSize.min, children: [CircularProgressIndicator(color: AppColors.teal), SizedBox(height: 14), Text('جارٍ التحقق من حالة الدفع...')]),
                ),
              ),
          ],
        ),
      ),
    );
  }
}

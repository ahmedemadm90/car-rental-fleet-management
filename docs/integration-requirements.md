# متطلبات تفعيل الإشعارات والدفع الخارجي

## Firebase Cloud Messaging

- يحتاج تطبيق Flutter إلى تهيئة Firebase وإضافة `firebase_messaging` ثم طلب إذن الإشعارات واسترجاع رمز الجهاز وتحديثه عند تغيّره.
- يحتاج خادم Laravel إلى اعتماد موثوق لإرسال الإشعارات، وملف بيانات اعتماد Firebase محفوظ خارج المستودع في متغير `FIREBASE_CREDENTIALS`.
- يحتاج iOS إلى تفعيل Push Notifications وBackground Modes ورفع مفتاح APNs إلى مشروع Firebase.
- يحتاج الويب إلى VAPID public key إن فُعّل Web Push.

المصادر: Firebase Cloud Messaging وFlutter get-started، تم الوصول إليهما في 13 أغسطس 2026.

## Paymob

- يدعم Paymob صفحة Checkout مستضافة وSDKs للموبايل، ولا يجب أن يعالج التطبيق أو الخادم بيانات البطاقة مباشرة.
- بوابة Paymob تعرض إنشاء Payment Intention عبر `POST /v1/intention` والتحقق من حالة معاملة عبر `GET /v1/transaction/{id}`.
- يلزم تزويد بيئة الخادم بمفتاح Paymob السري ومعرف تكامل الدفع/المعالجة، ثم تسجيل رابط Webhook من عنوان عام يمكن لـ Paymob الوصول إليه.

المصادر: Paymob Developer Portal، تم الوصول إليه في 13 أغسطس 2026.

## قرار التنفيذ في المستودع

سيحتوي الكود على قاعدة البيانات، جدولة فحص الاستحقاقات، سجل إشعارات داخل التطبيق، تسجيل رموز الأجهزة، إنشاء روابط Checkout محمية، والتحقق من Webhook. يظل الإرسال والتحصيل الحقيقيان معطلين تلقائياً إلى أن تضاف بيانات الاعتماد المطلوبة إلى `.env` وتتوافر استضافة تنفذ الجدولة وتستقبل Webhook.

## المصادر الرسمية

- Firebase Cloud Messaging for Flutter: https://firebase.google.com/docs/cloud-messaging/flutter/get-started
- Firebase Cloud Messaging overview: https://firebase.google.com/docs/cloud-messaging
- Paymob Developer Portal: https://developers.paymob.com/
- Paymob documentation index (LLM export): https://developers.paymob.com/paymob-docs/getting-started/overview/llms.txt
- Laravel FCM channel package: https://github.com/laravel-notification-channels/fcm

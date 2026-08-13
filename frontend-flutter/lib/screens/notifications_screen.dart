import 'package:car_rental_app/models/models.dart';
import 'package:car_rental_app/services/api_service.dart';
import 'package:car_rental_app/theme/app_theme.dart';
import 'package:flutter/material.dart';

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({super.key, required this.api});

  final ApiService api;

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  late Future<({List<AppNotificationItem> items, int unreadCount})> _notifications;

  @override
  void initState() {
    super.initState();
    _notifications = widget.api.notifications();
  }

  void _refresh() => setState(() => _notifications = widget.api.notifications());

  Future<void> _open(AppNotificationItem item) async {
    if (item.isUnread) {
      await widget.api.markNotificationRead(item.id);
      _refresh();
    }
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<({List<AppNotificationItem> items, int unreadCount})>(
      future: _notifications,
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) return const Center(child: CircularProgressIndicator());
        if (snapshot.hasError) return Center(child: Padding(padding: const EdgeInsets.all(24), child: Text(snapshot.error.toString(), textAlign: TextAlign.center)));
        final data = snapshot.data!;
        if (data.items.isEmpty) {
          return RefreshIndicator(onRefresh: () async => _refresh(), child: ListView(physics: const AlwaysScrollableScrollPhysics(), children: const [SizedBox(height: 110), Icon(Icons.notifications_none_rounded, size: 58, color: AppColors.muted), SizedBox(height: 14), Center(child: Text('لا توجد تنبيهات حالياً.'))]));
        }
        return RefreshIndicator(
          onRefresh: () async => _refresh(),
          child: ListView.separated(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 100),
            itemCount: data.items.length + 1,
            separatorBuilder: (_, _) => const SizedBox(height: 10),
            itemBuilder: (context, index) {
              if (index == 0) {
                return Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(color: AppColors.mint, borderRadius: BorderRadius.circular(18)),
                  child: Row(children: [const Icon(Icons.notifications_active_outlined, color: AppColors.teal), const SizedBox(width: 10), Expanded(child: Text(data.unreadCount == 0 ? 'أنت مطّلع على كل التنبيهات.' : 'لديك ${data.unreadCount} تنبيه غير مقروء.', style: const TextStyle(fontWeight: FontWeight.w700)))]),
                );
              }
              final item = data.items[index - 1];
              final color = item.severity == 'critical' ? const Color(0xFFD04D4D) : item.severity == 'warning' ? AppColors.warning : AppColors.teal;
              return Card(
                child: InkWell(
                  onTap: () => _open(item),
                  borderRadius: BorderRadius.circular(20),
                  child: Padding(
                    padding: const EdgeInsets.all(15),
                    child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Container(width: 42, height: 42, decoration: BoxDecoration(color: color.withValues(alpha: .12), borderRadius: BorderRadius.circular(13)), child: Icon(item.kind.contains('insurance') ? Icons.verified_user_outlined : Icons.build_circle_outlined, color: color)),
                      const SizedBox(width: 11),
                      Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(item.title, style: Theme.of(context).textTheme.titleMedium), const SizedBox(height: 4), Text(item.body, style: Theme.of(context).textTheme.bodySmall), const SizedBox(height: 8), Text(_relativeDate(item.createdAt), style: const TextStyle(color: AppColors.muted, fontSize: 11))])),
                      if (item.isUnread) Container(width: 8, height: 8, decoration: const BoxDecoration(color: AppColors.teal, shape: BoxShape.circle)),
                    ]),
                  ),
                ),
              );
            },
          ),
        );
      },
    );
  }
}

String _relativeDate(DateTime date) {
  final duration = DateTime.now().difference(date);
  if (duration.inMinutes < 60) return 'منذ ${duration.inMinutes.clamp(1, 59)} دقيقة';
  if (duration.inHours < 24) return 'منذ ${duration.inHours} ساعة';
  return 'منذ ${duration.inDays} يوم';
}

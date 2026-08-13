import 'package:flutter/material.dart';

import 'models/models.dart';
import 'services/api_service.dart';
import 'services/session_controller.dart';
import 'theme/app_theme.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  final api = ApiService();
  final session = SessionController(api);
  await session.restore();
  runApp(CarRentalApp(api: api, session: session));
}

class CarRentalApp extends StatelessWidget {
  const CarRentalApp({super.key, required this.api, required this.session});

  final ApiService api;
  final SessionController session;

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: session,
      builder: (context, _) => MaterialApp(
        title: 'رحلتي للسيارات',
        debugShowCheckedModeBanner: false,
        theme: buildAppTheme(),
        home: session.loading
            ? const Scaffold(body: Center(child: CircularProgressIndicator()))
            : session.isAuthenticated
                ? MainShell(api: api, session: session)
                : AuthScreen(api: api, session: session),
      ),
    );
  }
}

class AuthScreen extends StatefulWidget {
  const AuthScreen({super.key, required this.api, required this.session});
  final ApiService api;
  final SessionController session;

  @override
  State<AuthScreen> createState() => _AuthScreenState();
}

class _AuthScreenState extends State<AuthScreen> {
  final _formKey = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _email = TextEditingController();
  final _phone = TextEditingController();
  final _password = TextEditingController();
  bool _registering = false;
  bool _owner = false;
  bool _submitting = false;

  @override
  void dispose() {
    _name.dispose();
    _email.dispose();
    _phone.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _submitting = true);
    try {
      if (_registering) {
        await widget.session.register(
          name: _name.text.trim(),
          email: _email.text.trim(),
          phone: _phone.text.trim().isEmpty ? null : _phone.text.trim(),
          password: _password.text,
          role: _owner ? 'owner' : 'customer',
        );
      } else {
        await widget.session.login(_email.text.trim(), _password.text);
      }
    } on ApiException catch (e) {
      if (mounted) _showError(e.message);
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  void _showError(String message) => ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message), backgroundColor: Colors.red));

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        body: SafeArea(
          child: Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(24),
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 480),
                child: Form(
                  key: _formKey,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      const Icon(Icons.directions_car_filled, size: 72, color: Color(0xFF0D5C63)),
                      const SizedBox(height: 16),
                      Text('رحلتي للسيارات', textAlign: TextAlign.center, style: Theme.of(context).textTheme.headlineMedium?.copyWith(fontWeight: FontWeight.bold)),
                      const SizedBox(height: 8),
                      Text(_registering ? 'أنشئ حسابك للبدء' : 'سجّل دخولك لإدارة حجوزاتك', textAlign: TextAlign.center),
                      const SizedBox(height: 28),
                      if (_registering) ...[
                        TextFormField(controller: _name, decoration: const InputDecoration(labelText: 'الاسم الكامل'), validator: _required),
                        const SizedBox(height: 12),
                        TextFormField(controller: _phone, keyboardType: TextInputType.phone, decoration: const InputDecoration(labelText: 'رقم الهاتف (اختياري)')),
                        const SizedBox(height: 12),
                      ],
                      TextFormField(controller: _email, keyboardType: TextInputType.emailAddress, decoration: const InputDecoration(labelText: 'البريد الإلكتروني'), validator: _emailValidator),
                      const SizedBox(height: 12),
                      TextFormField(controller: _password, obscureText: true, decoration: const InputDecoration(labelText: 'كلمة المرور'), validator: (value) => (value == null || value.length < 8) ? 'كلمة المرور 8 أحرف على الأقل' : null),
                      if (_registering) SwitchListTile.adaptive(
                        contentPadding: EdgeInsets.zero,
                        title: const Text('أنا صاحب مكتب تأجير سيارات'),
                        value: _owner,
                        onChanged: (value) => setState(() => _owner = value),
                      ),
                      const SizedBox(height: 12),
                      FilledButton(
                        onPressed: _submitting ? null : _submit,
                        child: Padding(
                          padding: const EdgeInsets.symmetric(vertical: 12),
                          child: _submitting ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2)) : Text(_registering ? 'إنشاء الحساب' : 'تسجيل الدخول'),
                        ),
                      ),
                      TextButton(onPressed: () => setState(() => _registering = !_registering), child: Text(_registering ? 'لديك حساب بالفعل؟ سجّل الدخول' : 'ليس لديك حساب؟ أنشئ حساباً')),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class MainShell extends StatefulWidget {
  const MainShell({super.key, required this.api, required this.session});
  final ApiService api;
  final SessionController session;

  @override
  State<MainShell> createState() => _MainShellState();
}

class _MainShellState extends State<MainShell> {
  int _selected = 0;

  @override
  Widget build(BuildContext context) {
    final owner = widget.session.user!.isOwner;
    final screens = owner
        ? [OwnerDashboardScreen(api: widget.api), OwnerShopsScreen(api: widget.api), AccountScreen(session: widget.session)]
        : [CarsScreen(api: widget.api), MyBookingsScreen(api: widget.api), AccountScreen(session: widget.session)];
    final destinations = owner
        ? const [NavigationDestination(icon: Icon(Icons.dashboard_outlined), selectedIcon: Icon(Icons.dashboard), label: 'لوحة المتابعة'), NavigationDestination(icon: Icon(Icons.garage_outlined), selectedIcon: Icon(Icons.garage), label: 'الأسطول'), NavigationDestination(icon: Icon(Icons.person_outline), selectedIcon: Icon(Icons.person), label: 'حسابي')]
        : const [NavigationDestination(icon: Icon(Icons.search), selectedIcon: Icon(Icons.search), label: 'اكتشف السيارات'), NavigationDestination(icon: Icon(Icons.event_note_outlined), selectedIcon: Icon(Icons.event_note), label: 'حجوزاتي'), NavigationDestination(icon: Icon(Icons.person_outline), selectedIcon: Icon(Icons.person), label: 'حسابي')];
    final titles = owner ? ['لوحة المكتب', 'إدارة الأسطول', 'ملفي الشخصي'] : ['اكتشف سيارتك', 'حجوزاتي', 'ملفي الشخصي'];
    final subtitles = owner ? ['ملخص سريع لأداء أسطولك اليوم', 'سياراتك ومصروفاتها في مكان واحد', 'إعدادات الحساب والجلسة'] : ['اختر سيارة مناسبة لرحلتك القادمة', 'تابع طلباتك وحالة كل حجز', 'بيانات حسابك والجلسة'];
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        appBar: PreferredSize(
          preferredSize: const Size.fromHeight(92),
          child: Container(
            decoration: const BoxDecoration(gradient: LinearGradient(colors: [AppColors.navy, AppColors.teal], begin: Alignment.topRight, end: Alignment.bottomLeft)),
            child: SafeArea(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(20, 12, 20, 15),
                child: Row(children: [
                  Container(width: 42, height: 42, decoration: BoxDecoration(color: Colors.white.withValues(alpha: .16), borderRadius: BorderRadius.circular(14)), child: Icon(owner ? Icons.garage_rounded : Icons.directions_car_filled_rounded, color: Colors.white)),
                  const SizedBox(width: 12),
                  Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, mainAxisAlignment: MainAxisAlignment.center, children: [Text(titles[_selected], style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 19)), const SizedBox(height: 3), Text(subtitles[_selected], style: TextStyle(color: Colors.white.withValues(alpha: .76), fontSize: 12))])),
                  CircleAvatar(backgroundColor: Colors.white.withValues(alpha: .16), foregroundColor: Colors.white, child: Text(widget.session.user!.name.substring(0, 1).toUpperCase(), style: const TextStyle(fontWeight: FontWeight.w800))),
                ]),
              ),
            ),
          ),
        ),
        body: screens[_selected],
        bottomNavigationBar: NavigationBar(selectedIndex: _selected, onDestinationSelected: (index) => setState(() => _selected = index), destinations: destinations),
      ),
    );
  }
}

class CarsScreen extends StatefulWidget {
  const CarsScreen({super.key, required this.api});
  final ApiService api;

  @override
  State<CarsScreen> createState() => _CarsScreenState();
}

class _CarsScreenState extends State<CarsScreen> {
  final _city = TextEditingController();
  late Future<List<Car>> _cars;

  @override
  void initState() {
    super.initState();
    _cars = widget.api.searchCars();
  }

  @override
  void dispose() {
    _city.dispose();
    super.dispose();
  }

  void _search() => setState(() => _cars = widget.api.searchCars(city: _city.text.trim()));

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Container(
          margin: const EdgeInsets.fromLTRB(16, 16, 16, 10),
          padding: const EdgeInsets.all(18),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(22),
            gradient: const LinearGradient(colors: [AppColors.teal, Color(0xFF129A92)], begin: Alignment.topRight, end: Alignment.bottomLeft),
          ),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            const Text('رحلتك تبدأ من هنا', style: TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.w800)),
            const SizedBox(height: 5),
            Text('قارن السيارات واحجز المناسب لسفرك أو مناسبتك.', style: TextStyle(color: Colors.white.withValues(alpha: .8))),
            const SizedBox(height: 16),
            Row(children: [
              Expanded(child: TextField(controller: _city, onSubmitted: (_) => _search(), decoration: const InputDecoration(prefixIcon: Icon(Icons.location_city_outlined), labelText: 'اكتب المدينة', fillColor: Colors.white))),
              const SizedBox(width: 8),
              SizedBox(width: 52, height: 52, child: FilledButton(onPressed: _search, style: FilledButton.styleFrom(backgroundColor: AppColors.navy, padding: EdgeInsets.zero), child: const Icon(Icons.search_rounded))),
            ]),
          ]),
        ),
        Padding(padding: const EdgeInsets.fromLTRB(20, 6, 20, 10), child: Row(children: [Text('سيارات متاحة الآن', style: Theme.of(context).textTheme.titleMedium), const Spacer(), Text('اسحب للتحديث', style: Theme.of(context).textTheme.bodySmall)])),
        Expanded(
          child: FutureBuilder<List<Car>>(
            future: _cars,
            builder: (context, snapshot) {
              if (snapshot.connectionState != ConnectionState.done) return const Center(child: CircularProgressIndicator());
              if (snapshot.hasError) return ErrorState(error: snapshot.error.toString(), onRetry: _search);
              final cars = snapshot.data!;
              if (cars.isEmpty) return const Center(child: Text('لا توجد سيارات مطابقة للبحث حالياً.'));
              return RefreshIndicator(
                onRefresh: () async => _search(),
                child: ListView.separated(
                  physics: const AlwaysScrollableScrollPhysics(),
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 100),
                  itemCount: cars.length,
                  separatorBuilder: (_, _) => const SizedBox(height: 11),
                  itemBuilder: (context, index) => CarCard(
                    car: cars[index],
                    onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => CarDetailsScreen(api: widget.api, car: cars[index]))),
                  ),
                ),
              );
            },
          ),
        ),
      ],
    );
  }
}

class CarCard extends StatelessWidget {
  const CarCard({super.key, required this.car, required this.onTap});
  final Car car;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(15),
          child: Row(children: [
            Container(width: 74, height: 74, decoration: BoxDecoration(gradient: const LinearGradient(colors: [AppColors.sky, AppColors.mint], begin: Alignment.topRight, end: Alignment.bottomLeft), borderRadius: BorderRadius.circular(20)), child: const Icon(Icons.directions_car_filled_rounded, size: 39, color: AppColors.teal)),
            const SizedBox(width: 13),
            Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Row(children: [Expanded(child: Text(car.title, style: Theme.of(context).textTheme.titleMedium)), Container(padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4), decoration: BoxDecoration(color: const Color(0xFFE5F8EF), borderRadius: BorderRadius.circular(99)), child: const Text('متاحة', style: TextStyle(color: AppColors.success, fontSize: 11, fontWeight: FontWeight.w800)))]),
              const SizedBox(height: 5),
              Text('${car.year}  •  ${car.seats} مقاعد  •  ${car.rentalShop?.city ?? 'موقع غير محدد'}', style: Theme.of(context).textTheme.bodySmall),
              const SizedBox(height: 10),
              Row(children: [Text('${car.dailyRate.toStringAsFixed(0)} ج.م', style: const TextStyle(color: AppColors.teal, fontWeight: FontWeight.w800, fontSize: 16)), const SizedBox(width: 4), Text('/ يوم', style: Theme.of(context).textTheme.bodySmall), const Spacer(), const Icon(Icons.arrow_back_ios_new_rounded, size: 14, color: AppColors.muted)]),
            ])),
          ]),
        ),
      ),
    );
  }
}

class CarDetailsScreen extends StatefulWidget {
  const CarDetailsScreen({super.key, required this.api, required this.car});
  final ApiService api;
  final Car car;

  @override
  State<CarDetailsScreen> createState() => _CarDetailsScreenState();
}

class _CarDetailsScreenState extends State<CarDetailsScreen> {
  DateTime? _start;
  DateTime? _end;
  String _type = 'daily';
  final _location = TextEditingController();
  bool _submitting = false;

  @override
  void dispose() {
    _location.dispose();
    super.dispose();
  }

  Future<void> _date(bool start) async {
    final selected = await showDatePicker(context: context, firstDate: DateTime.now(), lastDate: DateTime.now().add(const Duration(days: 365)), initialDate: start ? (_start ?? DateTime.now()) : (_end ?? _start ?? DateTime.now()));
    if (selected != null) setState(() => start ? _start = selected : _end = selected);
  }

  Future<void> _book() async {
    if (_start == null || _end == null || _end!.isBefore(_start!)) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('اختر تاريخ بداية ونهاية صحيحين.')));
      return;
    }
    setState(() => _submitting = true);
    try {
      await widget.api.createBooking(carId: widget.car.id, rentalType: _type, startDate: _start!, endDate: _end!, pickupLocation: _location.text.trim());
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('تم إرسال طلب الحجز بنجاح.')));
        Navigator.pop(context);
      }
    } on ApiException catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message), backgroundColor: Colors.red));
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final car = widget.car;
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        appBar: AppBar(title: Text(car.title)),
        body: ListView(padding: const EdgeInsets.fromLTRB(16, 18, 16, 28), children: [
          Container(
            height: 190,
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(26),
              gradient: const LinearGradient(colors: [AppColors.navy, AppColors.teal], begin: Alignment.topRight, end: Alignment.bottomLeft),
            ),
            child: Stack(children: [
              Positioned(left: -30, bottom: -40, child: Container(width: 160, height: 160, decoration: BoxDecoration(shape: BoxShape.circle, color: Colors.white.withValues(alpha: .08)))),
              const Center(child: Icon(Icons.directions_car_filled_rounded, size: 108, color: Colors.white)),
              Positioned(top: 16, right: 16, child: Container(padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6), decoration: BoxDecoration(color: Colors.white.withValues(alpha: .16), borderRadius: BorderRadius.circular(99)), child: const Text('متاحة للحجز', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 12)))),
            ]),
          ),
          const SizedBox(height: 19),
          Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(car.title, style: Theme.of(context).textTheme.headlineSmall), const SizedBox(height: 5), Text('${car.year} • ${car.color ?? 'لون غير محدد'} • ${car.seats} مقاعد', style: Theme.of(context).textTheme.bodySmall)])),
            Container(padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 9), decoration: BoxDecoration(color: AppColors.mint, borderRadius: BorderRadius.circular(12)), child: Column(children: [Text('${car.dailyRate.toStringAsFixed(0)} ج.م', style: const TextStyle(color: AppColors.teal, fontWeight: FontWeight.w800)), const Text('لليوم', style: TextStyle(color: AppColors.muted, fontSize: 10))])),
          ]),
          const SizedBox(height: 14),
          Container(padding: const EdgeInsets.all(14), decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16), border: Border.all(color: const Color(0xFFE7EDF3))), child: Row(children: [const Icon(Icons.storefront_outlined, color: AppColors.teal), const SizedBox(width: 10), Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(car.rentalShop?.name ?? 'مكتب تأجير', style: const TextStyle(fontWeight: FontWeight.w800)), Text(car.rentalShop?.city ?? 'الموقع غير محدد', style: Theme.of(context).textTheme.bodySmall)])), const Icon(Icons.verified_rounded, color: AppColors.success, size: 20)])),
          if (car.weddingRate != null) Padding(padding: const EdgeInsets.only(top: 12), child: Text('سعر حفل الزفاف: ${car.weddingRate!.toStringAsFixed(0)} ج.م', style: const TextStyle(color: AppColors.warning, fontWeight: FontWeight.w700))),
          const SizedBox(height: 24),
          Text('تفاصيل الحجز', style: Theme.of(context).textTheme.titleMedium),
          const SizedBox(height: 12),
          DropdownButtonFormField(initialValue: _type, decoration: const InputDecoration(labelText: 'نوع التأجير', prefixIcon: Icon(Icons.category_outlined)), items: const [DropdownMenuItem(value: 'daily', child: Text('تأجير يومي')), DropdownMenuItem(value: 'travel', child: Text('سفر ورحلات')), DropdownMenuItem(value: 'wedding', child: Text('حفل زفاف'))], onChanged: (value) => setState(() => _type = value!)),
          const SizedBox(height: 12),
          Row(children: [Expanded(child: OutlinedButton.icon(onPressed: () => _date(true), icon: const Icon(Icons.calendar_month_outlined), label: Text(_start == null ? 'الاستلام' : _start!.toIso8601String().split('T').first))), const SizedBox(width: 9), Expanded(child: OutlinedButton.icon(onPressed: () => _date(false), icon: const Icon(Icons.event_available_outlined), label: Text(_end == null ? 'الإرجاع' : _end!.toIso8601String().split('T').first)))]),
          const SizedBox(height: 12),
          TextField(controller: _location, decoration: const InputDecoration(labelText: 'موقع الاستلام', prefixIcon: Icon(Icons.location_on_outlined))),
          const SizedBox(height: 22),
          FilledButton.icon(onPressed: _submitting ? null : _book, icon: const Icon(Icons.check_circle_outline), label: Text(_submitting ? 'جارٍ إرسال الطلب...' : 'إرسال طلب الحجز')),
          const SizedBox(height: 10),
          Text('سيتم تأكيد الحجز من مكتب التأجير قبل تحصيل أي مبلغ.', textAlign: TextAlign.center, style: Theme.of(context).textTheme.bodySmall),
        ]),
      ),
    );
  }
}

class MyBookingsScreen extends StatefulWidget {
  const MyBookingsScreen({super.key, required this.api});
  final ApiService api;
  @override
  State<MyBookingsScreen> createState() => _MyBookingsScreenState();
}

class _MyBookingsScreenState extends State<MyBookingsScreen> {
  late Future<List<Booking>> _bookings;
  @override
  void initState() { super.initState(); _bookings = widget.api.myBookings(); }
  void _refresh() => setState(() => _bookings = widget.api.myBookings());

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<List<Booking>>(
      future: _bookings,
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) {
          return const Center(child: CircularProgressIndicator());
        }
        if (snapshot.hasError) {
          return ErrorState(error: snapshot.error.toString(), onRetry: _refresh);
        }
        final rows = snapshot.data!;
        if (rows.isEmpty) return const Center(child: Text('لا توجد حجوزات حتى الآن.'));
        return RefreshIndicator(
          onRefresh: () async => _refresh(),
          child: ListView.separated(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 100),
            itemCount: rows.length,
            separatorBuilder: (_, _) => const SizedBox(height: 11),
            itemBuilder: (_, index) {
              final booking = rows[index];
              final color = _bookingColor(booking.status);
              return Card(
                child: Padding(
                  padding: const EdgeInsets.all(15),
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Row(children: [
                      Container(width: 44, height: 44, decoration: BoxDecoration(color: color.withValues(alpha: .12), borderRadius: BorderRadius.circular(14)), child: Icon(Icons.event_note_rounded, color: color)),
                      const SizedBox(width: 11),
                      Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(booking.car.title, style: Theme.of(context).textTheme.titleMedium), Text(booking.rentalType == 'wedding' ? 'حفل زفاف' : booking.rentalType == 'travel' ? 'سفر ورحلة' : 'تأجير يومي', style: Theme.of(context).textTheme.bodySmall)])),
                      Container(padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 6), decoration: BoxDecoration(color: color.withValues(alpha: .12), borderRadius: BorderRadius.circular(99)), child: Text(_bookingStatus(booking.status), style: TextStyle(color: color, fontSize: 11, fontWeight: FontWeight.w800))),
                    ]),
                    const Padding(padding: EdgeInsets.symmetric(vertical: 13), child: Divider(height: 1)),
                    Row(children: [const Icon(Icons.date_range_outlined, size: 17, color: AppColors.muted), const SizedBox(width: 6), Expanded(child: Text('${booking.startDate.toIso8601String().split('T').first}  ←  ${booking.endDate.toIso8601String().split('T').first}', style: Theme.of(context).textTheme.bodySmall)), Text('${booking.totalAmount.toStringAsFixed(0)} ج.م', style: const TextStyle(color: AppColors.teal, fontWeight: FontWeight.w800))]),
                  ]),
                ),
              );
            },
          ),
        );
      },
    );
  }
}

class OwnerDashboardScreen extends StatefulWidget {
  const OwnerDashboardScreen({super.key, required this.api});
  final ApiService api;
  @override
  State<OwnerDashboardScreen> createState() => _OwnerDashboardScreenState();
}

class _OwnerDashboardScreenState extends State<OwnerDashboardScreen> {
  late Future<DashboardSummary> _summary;
  @override
  void initState() { super.initState(); _summary = widget.api.dashboard(); }
  void _refresh() => setState(() => _summary = widget.api.dashboard());

  @override
  Widget build(BuildContext context) => FutureBuilder<DashboardSummary>(
    future: _summary,
    builder: (context, snapshot) {
      if (snapshot.connectionState != ConnectionState.done) return const Center(child: CircularProgressIndicator());
      if (snapshot.hasError) return ErrorState(error: snapshot.error.toString(), onRetry: _refresh);
      final data = snapshot.data!;
      return RefreshIndicator(
        onRefresh: () async => _refresh(),
        child: ListView(padding: const EdgeInsets.fromLTRB(16, 16, 16, 100), children: [
          Container(
            padding: const EdgeInsets.all(18),
            decoration: BoxDecoration(borderRadius: BorderRadius.circular(22), gradient: const LinearGradient(colors: [AppColors.navy, AppColors.teal], begin: Alignment.topRight, end: Alignment.bottomLeft)),
            child: Row(children: [Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [const Text('ملخص أداء الأسطول', style: TextStyle(color: Colors.white, fontSize: 19, fontWeight: FontWeight.w800)), const SizedBox(height: 6), Text('${data.availableCars} سيارة متاحة و ${data.rentedCars} مؤجرة الآن', style: TextStyle(color: Colors.white.withValues(alpha: .78)))])), Container(width: 48, height: 48, decoration: BoxDecoration(color: Colors.white.withValues(alpha: .14), borderRadius: BorderRadius.circular(15)), child: const Icon(Icons.insights_rounded, color: Colors.white))]),
          ),
          const SizedBox(height: 16),
          GridView.count(crossAxisCount: 2, childAspectRatio: 1.36, shrinkWrap: true, physics: const NeverScrollableScrollPhysics(), mainAxisSpacing: 10, crossAxisSpacing: 10, children: [MetricCard(label: 'إيرادات الشهر', value: '${data.monthRevenue.toStringAsFixed(0)} ج.م', icon: Icons.trending_up_rounded), MetricCard(label: 'مصروفات الشهر', value: '${data.monthExpenses.toStringAsFixed(0)} ج.م', icon: Icons.receipt_long_rounded), MetricCard(label: 'صافي الربح', value: '${data.monthNet.toStringAsFixed(0)} ج.م', icon: Icons.account_balance_wallet_rounded), MetricCard(label: 'حجوزات بانتظارك', value: '${data.pendingBookings}', icon: Icons.pending_actions_rounded)]),
          const SizedBox(height: 23),
          Row(children: [Text('تنبيهات الصيانة', style: Theme.of(context).textTheme.titleMedium), const Spacer(), Text('${data.maintenanceDue.length} تنبيه', style: const TextStyle(color: AppColors.warning, fontWeight: FontWeight.w800))]),
          const SizedBox(height: 10),
          if (data.maintenanceDue.isEmpty) Container(padding: const EdgeInsets.all(16), decoration: BoxDecoration(color: const Color(0xFFE7F8F0), borderRadius: BorderRadius.circular(16)), child: const Row(children: [Icon(Icons.check_circle_outline, color: AppColors.success), SizedBox(width: 9), Text('لا توجد استحقاقات صيانة عاجلة.')])),
          ...data.maintenanceDue.map((car) => Padding(padding: const EdgeInsets.only(bottom: 9), child: Card(child: ListTile(leading: Container(width: 40, height: 40, decoration: BoxDecoration(color: const Color(0xFFFFF1D8), borderRadius: BorderRadius.circular(12)), child: const Icon(Icons.build_circle_outlined, color: AppColors.warning)), title: Text('${car['make']} ${car['model']}'), subtitle: Text('لوحة: ${car['plate_number']}'), trailing: const Icon(Icons.chevron_left_rounded))))),
        ]),
      );
    },
  );
}

class OwnerShopsScreen extends StatefulWidget {
  const OwnerShopsScreen({super.key, required this.api});
  final ApiService api;
  @override
  State<OwnerShopsScreen> createState() => _OwnerShopsScreenState();
}

class _OwnerShopsScreenState extends State<OwnerShopsScreen> {
  late Future<List<RentalShop>> _shops;
  @override
  void initState() { super.initState(); _shops = widget.api.ownerShops(); }
  void _refresh() => setState(() => _shops = widget.api.ownerShops());

  Future<void> _addShop() async {
    final name = TextEditingController(); final city = TextEditingController(); final phone = TextEditingController();
    final saved = await showDialog<bool>(context: context, builder: (context) => Directionality(textDirection: TextDirection.rtl, child: AlertDialog(title: const Text('إضافة مكتب تأجير'), content: SingleChildScrollView(child: Column(mainAxisSize: MainAxisSize.min, children: [TextField(controller: name, decoration: const InputDecoration(labelText: 'اسم المكتب')), const SizedBox(height: 8), TextField(controller: city, decoration: const InputDecoration(labelText: 'المدينة')), const SizedBox(height: 8), TextField(controller: phone, keyboardType: TextInputType.phone, decoration: const InputDecoration(labelText: 'الهاتف'))])), actions: [TextButton(onPressed: () => Navigator.pop(context), child: const Text('إلغاء')), FilledButton(onPressed: () async { try { await widget.api.createShop({'name': name.text.trim(), 'city': city.text.trim(), 'phone': phone.text.trim()}); if (context.mounted) Navigator.pop(context, true); } on ApiException catch (e) { if (context.mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message))); } }, child: const Text('حفظ'))])));
    name.dispose(); city.dispose(); phone.dispose();
    if (saved == true) _refresh();
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<List<RentalShop>>(
      future: _shops,
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) {
          return const Center(child: CircularProgressIndicator());
        }
        if (snapshot.hasError) {
          return ErrorState(error: snapshot.error.toString(), onRetry: _refresh);
        }
        final shops = snapshot.data!;
        return Scaffold(
          floatingActionButton: FloatingActionButton.extended(
            onPressed: _addShop,
            icon: const Icon(Icons.add_business),
            label: const Text('إضافة مكتب'),
          ),
          body: shops.isEmpty
              ? const Center(child: Text('أضف مكتبك أولاً ثم ابدأ بإدارة سياراته.'))
              : RefreshIndicator(
                  onRefresh: () async => _refresh(),
                  child: ListView.separated(
                    padding: const EdgeInsets.all(12),
                    itemCount: shops.length,
                    separatorBuilder: (_, _) => const SizedBox(height: 8),
                    itemBuilder: (_, index) {
                      final shop = shops[index];
                      return Card(
                        child: ListTile(
                          leading: const Icon(Icons.garage),
                          title: Text(shop.name),
                          subtitle: Text('${shop.city} • ${shop.phone}'),
                          trailing: const Icon(Icons.chevron_left),
                          onTap: () => Navigator.of(context).push(
                            MaterialPageRoute(builder: (_) => OwnerCarsScreen(api: widget.api, shop: shop)),
                          ),
                        ),
                      );
                    },
                  ),
                ),
        );
      },
    );
  }
}

class OwnerCarsScreen extends StatefulWidget {
  const OwnerCarsScreen({super.key, required this.api, required this.shop});
  final ApiService api;
  final RentalShop shop;
  @override
  State<OwnerCarsScreen> createState() => _OwnerCarsScreenState();
}

class _OwnerCarsScreenState extends State<OwnerCarsScreen> {
  late Future<List<Car>> _cars;
  @override
  void initState() { super.initState(); _cars = widget.api.ownerCars(widget.shop.id); }
  void _refresh() => setState(() => _cars = widget.api.ownerCars(widget.shop.id));

  Future<void> _addCar() async {
    final make = TextEditingController(); final model = TextEditingController(); final year = TextEditingController(text: DateTime.now().year.toString()); final plate = TextEditingController(); final rate = TextEditingController();
    final saved = await showDialog<bool>(context: context, builder: (context) => Directionality(textDirection: TextDirection.rtl, child: AlertDialog(title: const Text('إضافة سيارة'), content: SingleChildScrollView(child: Column(mainAxisSize: MainAxisSize.min, children: [TextField(controller: make, decoration: const InputDecoration(labelText: 'الشركة المصنعة')), const SizedBox(height: 8), TextField(controller: model, decoration: const InputDecoration(labelText: 'الموديل')), const SizedBox(height: 8), TextField(controller: year, keyboardType: TextInputType.number, decoration: const InputDecoration(labelText: 'سنة الصنع')), const SizedBox(height: 8), TextField(controller: plate, decoration: const InputDecoration(labelText: 'رقم اللوحة')), const SizedBox(height: 8), TextField(controller: rate, keyboardType: TextInputType.number, decoration: const InputDecoration(labelText: 'السعر اليومي (ج.م)'))])), actions: [TextButton(onPressed: () => Navigator.pop(context), child: const Text('إلغاء')), FilledButton(onPressed: () async { try { await widget.api.createCar(widget.shop.id, {'make': make.text.trim(), 'model': model.text.trim(), 'year': int.parse(year.text), 'plate_number': plate.text.trim(), 'daily_rate': double.parse(rate.text), 'current_odometer_km': 0}); if (context.mounted) Navigator.pop(context, true); } on FormatException { if (context.mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('أدخل أرقاماً صحيحة للسنة والسعر.'))); } on ApiException catch (e) { if (context.mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message))); } }, child: const Text('حفظ'))])));
    make.dispose(); model.dispose(); year.dispose(); plate.dispose(); rate.dispose();
    if (saved == true) _refresh();
  }

  Future<void> _record(Car car, bool maintenance) async {
    final title = TextEditingController();
    final amount = TextEditingController();
    final odometer = TextEditingController();
    final saved = await showDialog<bool>(
      context: context,
      builder: (context) => Directionality(
        textDirection: TextDirection.rtl,
        child: AlertDialog(
          title: Text(maintenance ? 'تسجيل صيانة' : 'تسجيل مصروف'),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                TextField(controller: title, decoration: const InputDecoration(labelText: 'الوصف')),
                const SizedBox(height: 8),
                TextField(controller: amount, keyboardType: TextInputType.number, decoration: const InputDecoration(labelText: 'التكلفة (ج.م)')),
                const SizedBox(height: 8),
                TextField(controller: odometer, keyboardType: TextInputType.number, decoration: const InputDecoration(labelText: 'العداد الحالي (اختياري)')),
              ],
            ),
          ),
          actions: [
            TextButton(onPressed: () => Navigator.pop(context), child: const Text('إلغاء')),
            FilledButton(
              onPressed: () async {
                try {
                  final cost = double.parse(amount.text);
                  final km = odometer.text.trim().isEmpty ? null : int.parse(odometer.text);
                  final payload = <String, dynamic>{
                    'title': title.text.trim(),
                    if (maintenance) ...{
                      'type': 'other',
                      'service_date': DateTime.now().toIso8601String().split('T').first,
                      'cost': cost,
                    } else ...{
                      'category': 'other',
                      'amount': cost,
                      'expense_date': DateTime.now().toIso8601String().split('T').first,
                    },
                  };
                  if (km != null) {
                    payload['odometer_km'] = km;
                  }
                  if (maintenance) {
                    await widget.api.addMaintenance(car.id, payload);
                  } else {
                    await widget.api.addExpense(car.id, payload);
                  }
                  if (context.mounted) Navigator.pop(context, true);
                } on FormatException {
                  if (context.mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('أدخل تكلفة وعداداً صحيحين.')));
                } on ApiException catch (e) {
                  if (context.mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
                }
              },
              child: const Text('حفظ'),
            ),
          ],
        ),
      ),
    );
    title.dispose();
    amount.dispose();
    odometer.dispose();
    if (saved == true) _refresh();
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        appBar: AppBar(title: Text('سيارات ${widget.shop.name}')),
        floatingActionButton: FloatingActionButton.extended(
          onPressed: _addCar,
          icon: const Icon(Icons.add),
          label: const Text('إضافة سيارة'),
        ),
        body: FutureBuilder<List<Car>>(
          future: _cars,
          builder: (context, snapshot) {
            if (snapshot.connectionState != ConnectionState.done) {
              return const Center(child: CircularProgressIndicator());
            }
            if (snapshot.hasError) {
              return ErrorState(error: snapshot.error.toString(), onRetry: _refresh);
            }
            final cars = snapshot.data!;
            if (cars.isEmpty) return const Center(child: Text('لا توجد سيارات. أضف أول سيارة للأسطول.'));
            return RefreshIndicator(
              onRefresh: () async => _refresh(),
              child: ListView.separated(
                padding: const EdgeInsets.all(12),
                itemCount: cars.length,
                separatorBuilder: (_, _) => const SizedBox(height: 8),
                itemBuilder: (_, index) {
                  final car = cars[index];
                  return Card(
                    child: ListTile(
                      leading: const Icon(Icons.directions_car),
                      title: Text(car.title),
                      subtitle: Text('لوحة ${car.plateNumber} • ${car.dailyRate.toStringAsFixed(0)} ج.م/يوم'),
                      trailing: PopupMenuButton<String>(
                        onSelected: (value) {
                          if (value == 'expense') _record(car, false);
                          if (value == 'maintenance') _record(car, true);
                        },
                        itemBuilder: (_) => const [
                          PopupMenuItem(value: 'expense', child: Text('تسجيل مصروف')),
                          PopupMenuItem(value: 'maintenance', child: Text('تسجيل صيانة')),
                        ],
                      ),
                    ),
                  );
                },
              ),
            );
          },
        ),
      ),
    );
  }
}

class AccountScreen extends StatelessWidget {
  const AccountScreen({super.key, required this.session});
  final SessionController session;
  @override
  Widget build(BuildContext context) {
    final user = session.user!;
    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 20, 16, 32),
      children: [
        Container(
          padding: const EdgeInsets.all(20),
          decoration: BoxDecoration(borderRadius: BorderRadius.circular(22), gradient: const LinearGradient(colors: [AppColors.navy, AppColors.teal], begin: Alignment.topRight, end: Alignment.bottomLeft)),
          child: Column(children: [
            CircleAvatar(radius: 36, backgroundColor: Colors.white.withValues(alpha: .18), foregroundColor: Colors.white, child: Text(user.name.substring(0, 1).toUpperCase(), style: const TextStyle(fontSize: 27, fontWeight: FontWeight.w800))),
            const SizedBox(height: 12),
            Text(user.name, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 20)),
            const SizedBox(height: 4),
            Text(user.email, style: TextStyle(color: Colors.white.withValues(alpha: .76))),
            const SizedBox(height: 10),
            Container(padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6), decoration: BoxDecoration(color: Colors.white.withValues(alpha: .14), borderRadius: BorderRadius.circular(99)), child: Text(user.isOwner ? 'حساب صاحب مكتب تأجير' : 'حساب عميل', style: const TextStyle(color: Colors.white, fontSize: 12))),
          ]),
        ),
        const SizedBox(height: 18),
        Card(child: Column(children: [
          ListTile(leading: const Icon(Icons.verified_user_outlined, color: AppColors.teal), title: const Text('حساب موثق'), subtitle: const Text('يتم حفظ بياناتك وجلسة الدخول محلياً بأمان'), trailing: const Icon(Icons.check_circle, color: AppColors.success)),
          const Divider(height: 1),
          ListTile(leading: const Icon(Icons.support_agent_outlined, color: AppColors.teal), title: const Text('المساعدة والدعم'), subtitle: const Text('تواصل مع إدارة المنصة عند الحاجة'), trailing: const Icon(Icons.chevron_left_rounded)),
        ])),
        const SizedBox(height: 24),
        OutlinedButton.icon(onPressed: () => session.logout(), icon: const Icon(Icons.logout), label: const Text('تسجيل الخروج')),
      ],
    );
  }
}

class MetricCard extends StatelessWidget {
  const MetricCard({super.key, required this.label, required this.value, required this.icon});
  final String label; final String value; final IconData icon;
  @override
  Widget build(BuildContext context) => Card(child: Padding(padding: const EdgeInsets.all(14), child: Column(crossAxisAlignment: CrossAxisAlignment.start, mainAxisAlignment: MainAxisAlignment.center, children: [Container(width: 31, height: 31, decoration: BoxDecoration(color: AppColors.mint, borderRadius: BorderRadius.circular(10)), child: Icon(icon, color: AppColors.teal, size: 18)), const SizedBox(height: 10), Text(value, style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800)), Text(label, style: Theme.of(context).textTheme.bodySmall)])));
}

class ErrorState extends StatelessWidget {
  const ErrorState({super.key, required this.error, required this.onRetry});
  final String error; final VoidCallback onRetry;
  @override
  Widget build(BuildContext context) => Center(child: Padding(padding: const EdgeInsets.all(24), child: Column(mainAxisSize: MainAxisSize.min, children: [const Icon(Icons.cloud_off, size: 44), const SizedBox(height: 12), Text(error, textAlign: TextAlign.center), const SizedBox(height: 12), OutlinedButton(onPressed: onRetry, child: const Text('إعادة المحاولة'))])));
}

String? _required(String? value) => value == null || value.trim().isEmpty ? 'هذا الحقل مطلوب' : null;
String? _emailValidator(String? value) => value == null || !value.contains('@') ? 'أدخل بريداً إلكترونياً صحيحاً' : null;
String _bookingStatus(String status) => {'pending': 'بانتظار الموافقة', 'confirmed': 'مؤكد', 'active': 'جاري', 'completed': 'مكتمل', 'cancelled': 'ملغى', 'rejected': 'مرفوض'}[status] ?? status;
Color _bookingColor(String status) => {'pending': AppColors.warning, 'confirmed': AppColors.success, 'active': const Color(0xFF1478B8), 'completed': const Color(0xFF6750A4), 'cancelled': const Color(0xFFCC4F4F), 'rejected': const Color(0xFFCC4F4F)}[status] ?? AppColors.muted;

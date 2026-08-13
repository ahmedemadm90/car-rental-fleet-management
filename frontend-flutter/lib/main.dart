import 'package:flutter/material.dart';
import 'models/car.dart';
import 'services/api_service.dart';

void main() {
  runApp(const CarRentalApp());
}

class CarRentalApp extends StatelessWidget {
  const CarRentalApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'تطبيق تأجير السيارات وإدارة الأساطيل',
      theme: ThemeData(
        primarySwatch: Colors.blue,
        fontFamily: 'Cairo',
      ),
      locale: const Locale('ar', 'AE'),
      home: const MainHomeScreen(),
      debugShowCheckedModeBanner: false,
    );
  }
}

class MainHomeScreen extends StatefulWidget {
  const MainHomeScreen({super.key});

  @override
  State<MainHomeScreen> createState() => _MainHomeScreenState();
}

class _MainHomeScreenState extends State<MainHomeScreen> {
  int _currentIndex = 0;
  
  final List<Widget> _screens = [
    const CarsListScreen(),
    const AddExpenseScreen(),
    const BookingsScreen(),
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('منصة تأجير السيارات وإدارة الأساطيل'),
        centerTitle: true,
      ),
      body: _screens[_currentIndex],
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _currentIndex,
        onTap: (index) => setState(() => _currentIndex = index),
        items: const [
          BottomNavigationBarItem(icon: Icon(Icons.directions_car), label: 'السيارات'),
          BottomNavigationBarItem(icon: Icon(Icons.money), label: 'المصروفات والصيانة'),
          BottomNavigationBarItem(icon: Icon(Icons.bookmark), label: 'الحجوزات'),
        ],
      ),
    );
  }
}

class CarsListScreen extends StatefulWidget {
  const CarsListScreen({super.key});

  @override
  State<CarsListScreen> createState() => _CarsListScreenState();
}

class _CarsListScreenState extends State<CarsListScreen> {
  late Future<List<Car>> _carsFuture;

  @override
  void initState() {
    super.initState();
    _carsFuture = ApiService.getCars();
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<List<Car>>(
      future: _carsFuture,
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const Center(child: CircularProgressIndicator());
        } else if (snapshot.hasError) {
          return Center(child: Text('خطأ في تحميل البيانات: ${snapshot.error}'));
        } else if (!snapshot.hasData || snapshot.data!.isEmpty) {
          return const Center(child: Text('لا توجد سيارات مضافة'));
        }

        final cars = snapshot.data!;
        return ListView.builder(
          itemCount: cars.length,
          itemBuilder: (context, index) {
            final car = cars[index];
            return Card(
              margin: const EdgeInsets.all(8.0),
              child: ListTile(
                leading: const Icon(Icons.car_rental, size: 40, color: Colors.blue),
                title: Text('${car.name} (${car.model})'),
                subtitle: Text('رقم اللوحة: ${car.plateNumber} | السعر اليومي: ${car.dailyRate} ج.م'),
                trailing: Chip(
                  label: Text(car.status == 'available' ? 'متاح' : 'مؤجر'),
                  backgroundColor: car.status == 'available' ? Colors.green[100] : Colors.orange[100],
                ),
              ),
            );
          },
        );
      },
    );
  }
}

class AddExpenseScreen extends StatefulWidget {
  const AddExpenseScreen({super.key});

  @override
  State<AddExpenseScreen> createState() => _AddExpenseScreenState();
}

class _AddExpenseScreenState extends State<AddExpenseScreen> {
  final _titleController = TextEditingController();
  final _amountController = TextEditingController();
  final _notesController = TextEditingController();

  void _submitExpense() async {
    bool success = await ApiService.addExpense({
      'car_id': 1,
      'title': _titleController.text,
      'amount': double.parse(_amountController.text),
      'expense_date': DateTime.now().toIso8601String().split('T')[0],
      'notes': _notesController.text,
    });

    if (success) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('تم تسجيل المصروف والصيانة بنجاح')),
      );
      _titleController.clear();
      _amountController.clear();
      _notesController.clear();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.all(16.0),
      child: Column(
        children: [
          const Text('تسجيل مصروفات دورية أو صيانة (زيوت، إطارات، إصلاح)', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
          const SizedBox(height: 20),
          TextField(controller: _titleController, decoration: const InputDecoration(labelText: 'نوع المصروف أو الصيانة (مثلاً: تغيير زيت وسيفون)')),
          TextField(controller: _amountController, decoration: const InputDecoration(labelText: 'التكلفة (ج.م)'), keyboardType: TextInputType.number),
          TextField(controller: _notesController, decoration: const InputDecoration(labelText: 'ملاحظات إضافية')),
          const SizedBox(height: 20),
          ElevatedButton(onPressed: _submitExpense, child: const Text('حفظ المصروف')),
        ],
      ),
    );
  }
}

class BookingsScreen extends StatelessWidget {
  const BookingsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const Center(
      child: Text('قائمة الحجوزات (حفلات زفاف، سفر، تنقلات)', style: TextStyle(fontSize: 18)),
    );
  }
}

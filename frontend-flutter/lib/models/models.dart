class RentalShop {
  const RentalShop({
    required this.id,
    required this.name,
    required this.city,
    required this.phone,
    this.address,
    this.description,
  });

  final int id;
  final String name;
  final String city;
  final String phone;
  final String? address;
  final String? description;

  factory RentalShop.fromJson(Map<String, dynamic> json) => RentalShop(
        id: json['id'] as int,
        name: json['name'] as String,
        city: json['city'] as String,
        phone: json['phone'] as String,
        address: json['address'] as String?,
        description: json['description'] as String?,
      );
}

class Car {
  const Car({
    required this.id,
    required this.make,
    required this.model,
    required this.year,
    required this.plateNumber,
    required this.dailyRate,
    required this.status,
    required this.seats,
    this.weddingRate,
    this.color,
    this.features,
    this.imageUrl,
    this.rentalShop,
  });

  final int id;
  final String make;
  final String model;
  final int year;
  final String plateNumber;
  final double dailyRate;
  final double? weddingRate;
  final String status;
  final int seats;
  final String? color;
  final String? features;
  final String? imageUrl;
  final RentalShop? rentalShop;

  String get title => '$make $model';

  factory Car.fromJson(Map<String, dynamic> json) => Car(
        id: json['id'] as int,
        make: json['make'] as String,
        model: json['model'] as String,
        year: json['year'] as int,
        plateNumber: json['plate_number'] as String,
        dailyRate: double.parse(json['daily_rate'].toString()),
        weddingRate: json['wedding_rate'] == null
            ? null
            : double.parse(json['wedding_rate'].toString()),
        status: json['status'] as String,
        seats: json['seats'] as int? ?? 4,
        color: json['color'] as String?,
        features: json['features'] as String?,
        imageUrl: json['image_url'] as String?,
        rentalShop: json['rental_shop'] == null
            ? null
            : RentalShop.fromJson(json['rental_shop'] as Map<String, dynamic>),
      );
}

class AppUser {
  const AppUser({
    required this.id,
    required this.name,
    required this.email,
    required this.role,
    this.phone,
  });

  final int id;
  final String name;
  final String email;
  final String role;
  final String? phone;

  bool get isOwner => role == 'owner' || role == 'admin';

  factory AppUser.fromJson(Map<String, dynamic> json) => AppUser(
        id: json['id'] as int,
        name: json['name'] as String,
        email: json['email'] as String,
        role: json['role'] as String,
        phone: json['phone'] as String?,
      );
}

class Booking {
  const Booking({
    required this.id,
    required this.rentalType,
    required this.startDate,
    required this.endDate,
    required this.totalAmount,
    required this.status,
    required this.car,
  });

  final int id;
  final String rentalType;
  final DateTime startDate;
  final DateTime endDate;
  final double totalAmount;
  final String status;
  final Car car;

  factory Booking.fromJson(Map<String, dynamic> json) => Booking(
        id: json['id'] as int,
        rentalType: json['rental_type'] as String,
        startDate: DateTime.parse(json['start_date'] as String),
        endDate: DateTime.parse(json['end_date'] as String),
        totalAmount: double.parse(json['total_amount'].toString()),
        status: json['status'] as String,
        car: Car.fromJson(json['car'] as Map<String, dynamic>),
      );
}

class DashboardSummary {
  const DashboardSummary({
    required this.fleetSize,
    required this.availableCars,
    required this.rentedCars,
    required this.monthRevenue,
    required this.monthExpenses,
    required this.monthNet,
    required this.pendingBookings,
    required this.maintenanceDue,
  });

  final int fleetSize;
  final int availableCars;
  final int rentedCars;
  final double monthRevenue;
  final double monthExpenses;
  final double monthNet;
  final int pendingBookings;
  final List<dynamic> maintenanceDue;

  factory DashboardSummary.fromJson(Map<String, dynamic> json) => DashboardSummary(
        fleetSize: json['fleet_size'] as int,
        availableCars: json['available_cars'] as int,
        rentedCars: json['rented_cars'] as int,
        monthRevenue: double.parse(json['month_revenue'].toString()),
        monthExpenses: double.parse(json['month_expenses'].toString()),
        monthNet: double.parse(json['month_net'].toString()),
        pendingBookings: json['pending_bookings'] as int,
        maintenanceDue: json['maintenance_due'] as List<dynamic>,
      );
}

class PaymentSession {
  const PaymentSession({
    required this.id,
    required this.status,
    required this.amount,
    required this.checkoutUrl,
  });

  final int id;
  final String status;
  final double amount;
  final String? checkoutUrl;

  factory PaymentSession.fromJson(Map<String, dynamic> json) => PaymentSession(
        id: json['id'] as int,
        status: json['status'] as String,
        amount: double.parse(json['amount'].toString()),
        checkoutUrl: json['checkout_url'] as String?,
      );
}

class AppNotificationItem {
  const AppNotificationItem({
    required this.id,
    required this.title,
    required this.body,
    required this.kind,
    required this.severity,
    required this.createdAt,
    required this.readAt,
  });

  final String id;
  final String title;
  final String body;
  final String kind;
  final String severity;
  final DateTime createdAt;
  final DateTime? readAt;

  bool get isUnread => readAt == null;

  factory AppNotificationItem.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>;
    return AppNotificationItem(
      id: json['id'] as String,
      title: data['title'] as String? ?? 'تنبيه',
      body: data['body'] as String? ?? '',
      kind: data['kind'] as String? ?? 'general',
      severity: data['severity'] as String? ?? 'info',
      createdAt: DateTime.parse(json['created_at'] as String),
      readAt: json['read_at'] == null ? null : DateTime.parse(json['read_at'] as String),
    );
  }
}

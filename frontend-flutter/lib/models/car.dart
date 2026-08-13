class Car {
  final int id;
  final String name;
  final String model;
  final String plateNumber;
  final double dailyRate;
  final String status;

  Car({
    required this.id,
    required this.name,
    required this.model,
    required this.plateNumber,
    required this.dailyRate,
    required this.status,
  });

  factory Car.fromJson(Map<String, dynamic> json) {
    return Car(
      id: json['id'],
      name: json['name'],
      model: json['model'],
      plateNumber: json['plate_number'],
      dailyRate: double.parse(json['daily_rate'].toString()),
      status: json['status'],
    );
  }
}

import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'app_config.dart';

class ApiClient {
  ApiClient._();
  static final ApiClient instance = ApiClient._();
  static const storage = FlutterSecureStorage();
  Dio? _dio;

  Future<Dio> get dio async {
    final baseUrl = await AppConfig.getBaseUrl();
    if (_dio == null || _dio!.options.baseUrl != baseUrl) {
      _dio = Dio(BaseOptions(
        baseUrl: baseUrl,
        connectTimeout: const Duration(seconds: 20),
        receiveTimeout: const Duration(seconds: 60),
        sendTimeout: const Duration(seconds: 60),
        headers: {'Accept': 'application/json'},
      ));
      _dio!.interceptors.add(InterceptorsWrapper(
        onRequest: (options, handler) async {
          final token = await storage.read(key: 'auth_token');
          if (token != null && token.isNotEmpty) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          handler.next(options);
        },
      ));
    }
    return _dio!;
  }

  Future<void> reset() async => _dio = null;
}

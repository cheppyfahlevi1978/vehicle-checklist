import 'package:flutter/material.dart';
import 'auth_service.dart';
import 'home_page.dart';
import 'login_page.dart';
void main()=>runApp(const CourierApp());
class CourierApp extends StatelessWidget { const CourierApp({super.key}); @override Widget build(BuildContext context)=>MaterialApp(title:'IAS Market Courier',debugShowCheckedModeBanner:false,theme:ThemeData(colorScheme:ColorScheme.fromSeed(seedColor:Colors.green),useMaterial3:true),home:FutureBuilder<bool>(future:AuthService().hasSession(),builder:(c,s)=>!s.hasData?const Scaffold(body:Center(child:CircularProgressIndicator())):s.data!?const HomePage():const LoginPage(title:'IAS Market Courier',role:'courier',deviceName:'IAS Market Courier Android'))); }

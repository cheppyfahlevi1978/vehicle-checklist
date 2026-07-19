import 'package:flutter/material.dart';
import 'auth_service.dart';
import 'home_page.dart';
import 'login_page.dart';
void main()=>runApp(const MerchantApp());
class MerchantApp extends StatelessWidget { const MerchantApp({super.key}); @override Widget build(BuildContext context)=>MaterialApp(title:'IAS Market Merchant',debugShowCheckedModeBanner:false,theme:ThemeData(colorScheme:ColorScheme.fromSeed(seedColor:Colors.orange),useMaterial3:true),home:FutureBuilder<bool>(future:AuthService().hasSession(),builder:(c,s)=>!s.hasData?const Scaffold(body:Center(child:CircularProgressIndicator())):s.data!?const HomePage():const LoginPage(title:'IAS Market Merchant',role:'merchant',deviceName:'IAS Market Merchant Android'))); }

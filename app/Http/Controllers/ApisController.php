<?php

namespace App\Http\Controllers;

use App\Models\Carts;
use App\Models\MemberAddress;
use App\Models\Members;
use App\Models\Menu;
use App\Models\MenuCategories;
use App\Models\Menus;
use App\Models\OrderGoods;
use App\Models\Orders;
use App\Models\Shops;
use App\SignatureHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Mockery\Exception;


class ApisController extends Controller
{
    // 商家列表接口
    public function businessList(Request $request){

        if ($request->keyword!=null){
           $list = Shops::where('shop_name','like','%'.$request->keyword.'%')->get();

        }else{
            $list = Shops::all();
        }

//        $list = Shops::all();
        foreach ($list as $val){
            $val['distance']=mt_rand(100,5000);
            $val['estimate_time']=mt_rand(10,60);
        }

        $res = json_encode($list);
        return $res;
    }
    //获取指定商家接口
    public function business(Request $request){
        $res = Shops::select([
            "id",
            "shop_name",
            "shop_img",
            "shop_rating",
//                "service_code": 4.6,
//                "foods_code": 4.4,
//                "high_or_low": true,
//                "h_l_percent": 30,
            "brand",
            "on_time",
            "fengniao",
            "bao",
            "piao",
            "zhun",
            "start_send",
            "send_cost",
//                "distance": 637,
//                "estimate_time": 31,
            "notice",
            "discount"
        ])->where('id','=',$request->id)->first();
        $res['service_code']=4.6;
        $res['foods_code']=4.4;
        $res['high_or_low']='true';
        $res['distance']=637;
        $res['estimate_time']=31;

        $res['evaluate']=[[
            "user_id"=> 12344,
            "username"=> "w******k",
            "user_img"=>"http://www.homework.com/images/slider-pic4.jpeg",
            "time"=>date("Y-m-d H:i:s",time()),
            "evaluate_code"=> 1,
            "send_time"=>30,
            "evaluate_details"=> "还可以,将就吃!"
            ]
        ];
        $commoditys = MenuCategories::select([
                "id",
                "description",
                "is_selected",
                "name",
                "type_accumulation"

            ]
        )->where('shop_id',$request->id)->get();

        foreach ($commoditys as $commodity) {
            $goods_list = Menu::select([
                "id",
                "goods_name",
                "rating",
                "goods_price",
                "description",
                "month_sales",
                "rating_count",
                "tips",
                "satisfy_count",
                "satisfy_rate",
                "goods_img"
            ])->where('category_id', $commodity->id)->get();
            foreach ($goods_list as $val){
                $val['goods_id']=$val['id'];
            }

            $commodity['goods_list']=$goods_list;
        }

        $res['commodity']=$commoditys;

          return json_encode($res);

     }

    public function sms(Request $request){

        $tel = $request->tel;
        $params = array ();

        // *** 需用户填写部分 ***

        // fixme 必填: 请参阅 https://ak-console.aliyun.com/ 取得您的AK信息
        $accessKeyId = "LTAI5zqIhn36ug5o";
        $accessKeySecret = "Xl4s7PHRU7jTchYzutxyCT693rUZUM";

        // fixme 必填: 短信接收号码
        $params["PhoneNumbers"] = $tel;

        // fixme 必填: 短信签名，应严格按"签名名称"填写，请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/sign
        $params["SignName"] = "廖昆";

        // fixme 必填: 短信模板Code，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
        $params["TemplateCode"] = "SMS_140585015";

        // fixme 可选: 设置模板参数, 假如模板中存在变量需要替换则为必填项
        $code = rand(1000,9999);
        $params['TemplateParam'] = Array (
            "code" => $code,
           // "product" => "阿里通信"
        );

        // fixme 可选: 设置发送短信流水号
        $params['OutId'] = "12345";

        // fixme 可选: 上行短信扩展码, 扩展码字段控制在7位或以下，无特殊需求用户请忽略此字段
        $params['SmsUpExtendCode'] = "1234567";


        // *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
        if(!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
            $params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
        }

        // 初始化SignatureHelper实例用于设置参数，签名以及发送请求
        $helper = new SignatureHelper();

            // 此处可能会抛出异常，注意catch
            $content = $helper->request(

                $accessKeyId,
                $accessKeySecret,
                "dysmsapi.aliyuncs.com",
                array_merge($params, array(
                        "RegionId" => "cn-hangzhou",
                        "Action" => "SendSms",
                        "Version" => "2017-05-25",
                    )
                )
            // fixme 选填: 启用https
            // ,true
            );

        //将短信验证码保存在redis里面

        Redis::set('code_'.$tel,$code);
        Redis::expire('code','300');

        return json_encode([
            "status"=>"true",
            "message"=>"获取短信验证码成功"
        ]);


    }

    //用户注册 接口
    public function regist(Request $request){

        $validator = Validator::make($request->all(),[
            'username'=>'required|unique:members',
            'tel'=>'required|unique:members',
            'sms'=>'required',
            'password'=>'required',
        ],[
            'username.required'=>'用户名不能为空',
            'username.unique'=>'用户名重复',
            'tel.required'=>'电话号码不能为空',
            'tel.unique'=>'电话号码重复',
            'sms.required'=>'验证码不能为空',
            'password.required'=>'密码不能为空',

        ]);
        if ($validator->fails()){
            return [
                "status"=> "false",
                "message"=>$validator->errors()->first(),
            ];
        }
        $code = Redis::get('code_'.$request->tel);
        if ($request->sms != $code){
            return json_encode([
                "status"=> "false",
                "message"=>"验证码错误!"
            ]);
        }
        //对密码进行加密处理
        $password = bcrypt($request->password);
        Members::create([
            'username'=>$request->username,
            'tel'=>$request->tel,
            'password'=>$password,
            ]);
;
        return json_encode([
            "status"=> "true",
            "message"=>"注册成功"
        ]);

    }
    public function loginCheck(Request $request){
        $validator = Validator::make($request->all(),[
            'name'=>'required',
            'password'=>'required',
        ],[
            'name.required'=>'用户名不能为空',
            'password.required'=>'密码不能为空',

        ]);
        if ($validator->fails()){
            return [
                "status"=> "false",
                "message"=>$validator->errors()->first(),
            ];
        }
//        return json_encode([
//            "status"=>"true",
//            "message"=>"登录成功",
//            "user_id"=>1,
//            "username"=>'张三'
//        ]);
        if (Auth::attempt(['username' => $request->name, 'password' => $request->password])){

            return json_encode([
                "status"=>"true",
                "message"=>"登录成功",
                "user_id"=>Auth::user()->id,
                "username"=>Auth::user()->username
                ]);
            }else{
            return json_encode([
                "status"=>"false",
                "message"=>"登录失败",

            ]);

        }

    }
    public function addressList(){
        $address = MemberAddress::where('member_id',Auth::user()->id)->get()->makeHidden(['county','member_id',
            'is_default','created_at','updated_at']);
        foreach ($address as $val){
            $val['area']=$val->county;
            $val['detail_address']=$val->address;
        }
        return json_encode($address);
    }
    public function addAddress(Request $request){

        $validator = Validator::make($request->all(),[
            'name'=>'required',
            'tel'=>'required',
            'provence'=>'required',
            'city'=>'required',
            'area'=>'required',
            'detail_address'=>'required',
        ],[
            'name.required'=>'收件人不能为空',
            'tel.required'=>'电话号码不能为空',

            'provence.required'=>'省分不能为空',
            'city.required'=>'城市不能为空',
            'area.required'=>'县不能为空',
            'detail_address.required'=>'详细地址不能为空',

        ]);
        if ($validator->fails()){
            return [
                "status"=> "false",
                "message"=>$validator->errors()->first(),
            ];
        }

        MemberAddress::create([
            'name'=>$request->name,
            'tel'=>$request->tel,
            'province'=>$request->provence,
            'city'=>$request->city,
            'county'=>$request->area,
            'address'=>$request->detail_address,
            'member_id'=>Auth::user()->id,
        ]);
        return json_encode([
            "status"=> "true",
            "message"=>"添加成功"
        ]);
    }
    public function address(Request $request){

        $res = DB::table('member_addresses')->where('id',$request->id)->get();

           return json_encode([
               "id"=>$res[0]->id,
             "provence"=>$res[0]->province,
             "city"=>$res[0]->city,
             "area"=>$res[0]->county,
             "detail_address"=>$res[0]->address,
             "name"=>$res[0]->name,
             "tel"=>$res[0]->tel,
           ]);

    }
    public function editAddress(Request $request){

        $validator = Validator::make($request->all(),[
            'name'=>'required',
            'tel'=>'required',
            'provence'=>'required',
            'city'=>'required',
            'area'=>'required',
            'detail_address'=>'required',
        ],[
            'name.required'=>'收件人不能为空',
            'tel.required'=>'电话号码不能为空',

            'provence.required'=>'省分不能为空',
            'city.required'=>'城市不能为空',
            'area.required'=>'县不能为空',
            'detail_address.required'=>'详细地址不能为空',

        ]);
        if ($validator->fails()){
            return [
                "status"=> "false",
                "message"=>$validator->errors()->first(),
            ];
        }

        DB::update("update member_addresses set
        name='{$request->name}',
        tel='{$request->tel}',
        province='{$request->provence}',
        city='{$request->city}',
        county='{$request->area}',
        address='{$request->detail_address}'
        where id = ?", [$request->id]);

        return json_encode([
            "status"=>"true",
            "message"=> "修改成功"
        ]);
    }
    public function addCart(Request $request){
        $goodsList = $request->goodsList;
        $amount = $request->goodsCount;
        $member_id = Auth::user()->id;
        Carts::where('member_id',$member_id)->delete();//清空购物车
        for ($i=0 ;$i<count($goodsList);++$i){
            Carts::create([//清空购物车之后添加新的商品
                'goods_id'=>$goodsList[$i],
                'amount'=>$amount[$i],
                'member_id'=>$member_id,
            ]);
//            DB::insert('insert into carts (goods_id, amount,member_id) values (?,?,?)', [$goodsList[$i],
//                $amount[$i],Auth::user()->id]);
        }
        return json_encode([
            "status"=> "true",
             "message"=> "添加成功"
        ]);
    }
    public function cart(){

      $carts = Carts::where('member_id',Auth::user()->id)->get();
       $goods_list[] = [];$totalCost = 0;
        foreach ($carts as $cart){
            $menus = Menu::select('goods_name','goods_price','goods_img')
                ->where('id',$cart->goods_id)->first();
            $goods_list[]=[
                'goods_id'=>$cart->goods_id,
                'goods_name'=>$menus->goods_name,
                'goods_img'=>$menus->goods_img,
                'amount'=>$cart->amount,
                'goods_price'=>$menus->goods_price,
            ];
            $totalCost+=($menus->goods_price)*($cart->amount);
        }
        $date['goods_list']=$goods_list;
        $date['totalCost']=$totalCost;
        return json_encode($date);


    }
    public function addorder(Request $request){
        $member_id = Auth::user()->id;
        $address = MemberAddress::where('id',$request->address_id)->first();

        $goods = Carts::where('member_id',$member_id)->first();
        $shops = Menu::where('id',$goods->goods_id)->first();

        $amounts = Carts::where('member_id',$member_id)->get();
        $total='';
        foreach ($amounts as $val){
            $goods_price = Menu::where('id',$val->goods_id)->first()->goods_price;
            $amount = $val->amount;
            $total+=$goods_price*$amount;
        }

        $sn = 'sn'.date('YmdHis',time()).mt_rand(1000,9999);

        $out_trade_no = mt_rand(1000,9999);
        DB::beginTransaction();//开启事务
        try{
        $res = Orders::create([
            'member_id'=>$member_id,
            'shop_id'=>$shops->shop_id,
            'sn'=>$sn,
            'province'=>$address->province,
            'city'=>$address->city,
            'county'=>$address->county,
            'address'=>$address->address,
            'tel'=>$address->tel,
            'name'=>$address->name,
            'total'=>$total,
            'status'=>0,
            'out_trade_no'=>$out_trade_no,
        ]);
        $order_id = $res->id;
        $amounts = Carts::where('member_id',$member_id)->get();
        foreach ($amounts as $val){
            $goods_msg = Menu::where('id',$val->goods_id)->first();
            OrderGoods::create([
                'order_id'=>$order_id,
                'goods_id'=>$val->goods_id,
                'amount'=>$val->amount,
                'goods_name'=>$goods_msg->goods_name,
                'goods_img'=>$goods_msg->goods_img,
                'goods_price'=>$goods_msg->goods_price,
             ]);
           }
           DB::commit();
        }catch (Exception $exception){
            DB::rollBack();
            return json_encode([
                "status"=>"false",
                "message"=> "添加订单失败",
            ]);
        }
        return json_encode([
              "status"=>"true",
              "message"=> "添加成功",
              "order_id"=>$order_id
        ]);
    }
    public function order(Request $request){

        $orders = Orders::where('id',$request->id)->first();
        $address =$orders->province.$orders->city.$orders->county.$orders->address;
        $shops = Shops::where('id',$orders->shop_id)->first();
        $time = date('Y-m-d H:i',strtotime($orders->created_at));
        $res = [
            "id"=>$request->id,
            "order_code"=>$orders->sn,
            "order_birth_time"=>$time,
            "order_status"=> $orders->status==0?'代付款':'',
            "shop_id"=>$orders->shop_id,
            "shop_name"=>$shops->shop_name,
            "shop_img"=>$shops->shop_img,

            "order_price"=>$orders->total-0,//字符串转为数字
            "order_address"=>$address,
        ];
        $order_goods = OrderGoods::where('order_id',$request->id)->get();
        $goods_list[] = [];
        foreach ($order_goods as $val){
            $goods_list[]=$val;
        }
        $res['goods_list']=$goods_list;
        return json_encode($res);
    }
    public function pay(){
        return json_encode([
            "status"=> "true",
             "message"=>"支付成功"
        ]);
    }
    public function orderList(){
        $order_list = Orders::where('member_id',Auth::user()->id)->get()->makeHidden(['tel']);
        foreach ($order_list as &$val){
            $shops = Shops::where('id',$val->shop_id)->first();

            $val['order_code']=$val->sn;
            $val['order_birth_time']=date('Y-m-d H:i',strtotime($val->created_at));
            $val['order_status']=$val->status==0?'未付款':'已完成';
//            "shop_id": "1",
            $val['shop_name']=$shops->shop_name;
            $val['shop_img']=$shops->shop_img;
            $order_goods = OrderGoods::where('order_id',$val->id)->get();

            $val['goods_list']=$order_goods;

        }
        return json_encode($order_list);
    }
    public function changePassword(Request $request){
        $dbpassword = Members::where('id',Auth::user()->id)->first()->password;
        if (!Hash::check($request->oldPassword, $dbpassword)) {
            return json_encode([
                "status"=>"false",
                "message"=>"旧密码错误"
            ]);
        }
        $password = bcrypt($request->newPassword);
        DB::table('members')
            ->where('id', Auth::user()->id)
            ->update(['password' =>$password]);
        return json_encode([
            "status"=>"true",
            "message"=>"修改成功"
        ]);
    }
    public function forgetPassword(Request $request){
        $validator = Validator::make($request->all(),[
            'tel'=>'required',
            'sms'=>'required',
            'password'=>'required',
        ],[

            'tel.required'=>'电话号码不能为空',
            'tel.unique'=>'电话号码重复',
            'sms.required'=>'验证码不能为空',
            'password.required'=>'密码不能为空',

        ]);

        if ($validator->fails()){
            return [
                "status"=> "false",
                "message"=>$validator->errors()->first(),
            ];
        }
        $count = Members::where('tel',$request->tel)->first();
        if ($count==null){
            return [
                "status"=> "false",
                "message"=>'该电话号码不存在!',
            ];
        }
        $code = Redis::get('code_'.$request->tel);
        if ($request->sms != $code){
            return json_encode([
                "status"=> "false",
                "message"=>"验证码错误!"
            ]);
        }
        //对密码进行加密处理
        $password = bcrypt($request->password);
        DB::table('members')
            ->where('tel', $request->tel)
            ->update(['password' =>$password]);
        return json_encode([
            "status"=> "true",
            "message"=>"重置密码成功"
        ]);
    }

}
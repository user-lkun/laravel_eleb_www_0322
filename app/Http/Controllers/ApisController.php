<?php

namespace App\Http\Controllers;

use App\Models\Members;
use App\Models\Menu;
use App\Models\MenuCategories;
use App\Models\Menus;
use App\Models\Shops;
use App\SignatureHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;



class ApisController extends Controller
{
    // 商家列表接口
    public function businessList(Request $request){
//        $where = [];
//        if ($request->keyword!=null){
//            $where=['shop_name','like','%'.$request->keyword.'%'];
//
//        }
//        $list = Shops::where($where)->paginate();
        $list = Shops::all();
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
            "evaluate_details"=> "不怎么好吃"
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
//                "goods_id",
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
            ))
        // fixme 选填: 启用https
        // ,true
        );
        //将短信验证码保存在redis里面
//        Redis::set('code',$params['TemplateParam']['code']);
        Redis::set('code',$code);
        Redis::expire('code','60');
//        return json_encode($content);
        return json_encode([
            "status"=>"true",
            "message"=>"获取短信验证码成功"
        ]);


    }

    //用户注册 接口
    public function regist(Request $request){

        $code = Redis::get('code');
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
}
<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\MenuCategories;
use App\Models\Menus;
use App\Models\Shops;
use Illuminate\Http\Request;


class ApisController extends Controller
{
    // 商家列表接口
    public function businessList(){

        $list = Shops::all();
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
            "evaluate_details"=> "不怎么好吃"
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
//                "goods_id",
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
            $commodity['goods_list']=$goods_list;
        }

        $res['commodity']=$commoditys;

          return json_encode($res);

     }
    //用户注册 接口
    public function regist(){
        return json_encode([
             "status"=> "true",
             "message"=>"注册成功"
        ]);exit;

    }
}

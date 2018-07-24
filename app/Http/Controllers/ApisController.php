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

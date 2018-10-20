<?php

namespace app\controllers;
use yii;
use app\models\User;
use yii\web\Response;
use app\models\Payment;
use app\models\TypePay;

use Imagine\Image\Box;
use yii\imagine\Image;
use yii\web\UploadedFile;

class ApiController extends \yii\web\Controller
{
    
    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionLogin(){
        
        $model=User::find()->where(['username'=>$_POST['username'],'password'=>$_POST['password']])->asArray()->one();
        if(!empty($model))
        {
            \Yii::$app->response->format=Response::FORMAT_JSON;
            return $model;
        }else{
            $model=['error'=>'ຊື່​ເຂົ້າ​ລະ​ບ​ົບ ຫຼຶ​ ລະ​ຫັດ​ຜ່ານ​ບໍ​ຖືກ​ຕ້ອງ'];
            \Yii::$app->response->format=Response::FORMAT_JSON;
            return $model;
        }
    }

    public function actionHome(){
        $sum_pay =\Yii::$app->db->createCommand('SELECT sum(amount) FROM payment where year(date)='.date('Y').'')->queryScalar();
        $sum_recive =\Yii::$app->db->createCommand('SELECT sum(amount) FROM recieve_money where year(date)='.date('Y').'')->queryScalar();
        $percent_pay=($sum_pay*100)/$sum_recive;
        $percent_recive=100-$percent_pay;
        $result=['total_pay'=>\number_format($sum_pay,2),'total_recieve'=>\number_format($sum_recive,2),'percent_pay'=>sprintf('%0.2f',$percent_pay),'percent_recive'=>sprintf('%0.2f',$percent_recive)];
       
        $pay_car =\Yii::$app->db->createCommand('SELECT sum(amount) FROM dao_car where status IN("Paid","Saving")')->queryScalar();
        $still_pay=17000-$pay_car;
        $result_car=['pay_car'=>number_format($pay_car,2),'still_car'=>number_format($still_pay,2)];
        
        $result=array_merge($result,$result_car);

        \Yii::$app->response->format=Response::FORMAT_JSON;
        return $result;
    }

    public function actionUplaodfile()
    {
        $uploads = UploadedFile::getInstancesByName("upfile");
        if (empty($uploads)) {
            return "Must upload at least 1 file in upfile form-data POST";
        }

        // $uploads now contains 1 or more UploadedFile instances
        $savedfiles = null;
        foreach ($uploads as $file) {
            $realFileName = rand(). time() . '.' . $file->extension;
            $path = \Yii::$app->basePath . '/web/images/' . $realFileName; //Generate your save file path here;
            if ($file->saveAs($path)) {
                $savedfiles = $realFileName;
                $imagine = Image::getImagine();
                $image = $imagine->open(\Yii::$app->basePath . '/web/images/' . $savedfiles);
                if(isset($_POST['name']) && ($_POST['name']=="profile_img" || $_POST['name']=="profileBg_img"))
                {
                    $image->save(\Yii::$app->basePath . '/web/images/small/' . $savedfiles, ['quality' => 60]);
                }
                
            } else {
                $savedfiles = 'Error save file';
            } //Your uploaded file is saved, you can process it further from here
        }

        /*======== Use for update profile profile bg ========*/
        if(isset($_POST['edit']))
        {
            if(isset($_POST['name']) && $_POST['name']=='profile_img')
            {
                User::updateAll(['photo' =>$savedfiles], 'id='.$_POST['userid'].'');
            }elseif(isset($_POST['name']) && $_POST['name']=='profileBg_img')
            {
                User::updateAll(['bg_photo' =>$savedfiles], 'id='.$_POST['userid'].'');
            }
        }
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $savedfiles;

    }

    public function actionListpayment($user_id){
        $model=Payment::find()
        ->joinWith(['typePay','user'])
        ->asArray()->orderby('id DESC ,date DESC')->all();
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $model;
    }

    public function actionListtypepay(){
        $model=TypePay::find()->all();
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $model;
    }

    public function actionCreatepayment(){
        $model=new Payment();
        if(isset($_POST['amount']))
        {
            $model->amount=$_POST['amount'];
            $model->description=$_POST['description'];
            $model->date=date('Y-m-d',strtotime($_POST['date']));
            $model->refer_id=substr(md5(mt_rand()),0,7).date('Ymdhis');
            $model->type_pay_id=$_POST['type_id'];
            $model->user_id=$_POST['user_id'];
            if($model->save())
            {
                $result=true;
            }else{
                $result='ທ່ານ​ຕ້ອງ​ປ້ອນ​ຂໍ້​ມ​ູນ​ໃຫ້​ຄອບ';
            }
            \Yii::$app->response->format = Response::FORMAT_JSON;
            return $result;
        }
    }

}
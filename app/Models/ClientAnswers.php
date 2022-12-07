<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientAnswers extends Model
{
    use HasFactory;

    protected $fillable =[
        'user_id',
        'type_id',
        'question_id',
        'answer_id',
        'description',
    ];

    public function user(){
        return $this->hasOne(User::class,'user_id','id');
    }
    public function questionType(){
        return $this->hasOne(QuestionType::class,'type_id','id');
    }
    public function question(){
        return $this->hasOne(QuestionType::class,'question_id','id');
    }
    public function answer(){
        return $this->hasOne(QuestionType::class,'answer_id','id');
    }
}

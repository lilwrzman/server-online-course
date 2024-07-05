<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'role',
        'info',
        'corporate_id',
        'avatar',
        'status',
        'email_verified_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'verification_token'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'info' => 'json'
    ];

    public function feedbacks()
    {
        return $this->hasMany(CourseFeedback::class);
    }

    public function corporate()
    {
        return $this->belongsTo(User::class, 'corporate_id');
    }

    public function corporateStudents()
    {
        return $this->hasMany(User::class, 'corporate_id');
    }

    public function corporateStudentCount()
    {
        return $this->corporateStudents()->count();
    }

    public function courses(){
        return $this->hasMany(Course::class, 'teacher_id');
    }

    public function teacherCourseCount()
    {
        return $this->courses()->count();
    }

    public function courseAccesses()
    {
        return $this->hasMany(CourseAccess::class, 'user_id');
    }

    public function courseAccessesCorporate()
    {
        return $this->hasMany(CourseAccess::class)->where('type', "Corporate");
    }

    public function myCourses(){
        return $this->belongsToMany(Course::class, 'course_accesses')->withPivot('access_date');
    }

    public function courseAccessCount()
    {
        return $this->courseAccesses()->count();
    }

    public function referral()
    {
        return $this->hasOne(Referral::class, 'corporate_id');
    }

    public function getReferralCode()
    {
        return $this->referral()->first()->code ?? null;
    }

    public static function generateReferralCode()
    {
        do {
            $code = Str::random(10);
        } while (Referral::where('code', $code)->exists());

        return $code;
    }

    public function bundles()
    {
        return $this->hasMany(CourseBundle::class);
    }

    public function redeemHistory()
    {
        return $this->hasMany(RedeemHistory::class);
    }

    public function studentCourses()
    {
        return $this->belongsToMany(Course::class, 'course_accesses');
    }

    public function hasAccessToCourse($course_id)
    {
        return $this->studentCourses()->where('course_id', $course_id)->exists();
    }

    public function studentProgress()
    {
        return $this->hasMany(StudentProgress::class, 'user_id');
    }

    public function assessmentHistories()
    {
        return $this->hasMany(AssessmentHistory::class, 'user_id');
    }

    public function myTransaction()
    {
        return $this->hasMany(Transaction::class, 'user_id');
    }
}

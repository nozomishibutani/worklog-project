<?php

namespace Tests\Feature\Auth;

use App\Enums\ApprovalStatus;
use App\Enums\LoginForm;
use App\Enums\Role;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\AttendanceChange;
use App\Models\BreakTimeChange;
use Carbon\Carbon;
use App\Services\AttendanceUpdateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class EditAttendanceTest extends TestCase
{
    /**
     * 勤怠情報修正機能（管理者）
     */
    use RefreshDatabase;

    /**
     * 承認待ちの修正申請が全て表示されている
     */
    #[Test]
    public function adminCanGetAllUserDetail()
    {
        $users = User::factory(3)->create();
        $now = now();
        foreach ($users as $user) {
            $attendance = Attendance::factory()->create([
                'user_id' => $user->id,
                'work_date' => $now->copy()->startOfMonth()->format('Y-m-d'),
                'clock_in' => $now->copy()->startOfMonth()->format('Y-m-d H:i:s'),
                'clock_out' => $now->copy()->startOfMonth()->addHour()->format('Y-m-d H:i:s'),
            ]);
            $breakTimeChange = BreakTime::factory()->create([
                        'attendance_id' => $attendance->id,
                        'clock_in' => $now->copy()->startOfMonth()->subMinutes(45)->format('Y-m-d H:i:s'),
                        'clock_out' => $now->copy()->startOfMonth()->subMinutes(30)->format('Y-m-d H:i:s'),
            ]);
            $attendanceChange = AttendanceChange::factory()->create([
                            'attendance_id' => $attendance->id,
                            'user_id' => $user->id,
                            'work_date' => $attendance->work_date,
                            'clock_in' => $attendance->clock_in,
                            'clock_out' => $now->copy()->startOfMonth()->addHour($user->id)->format('Y-m-d H:i:s'),
                            'note' => '備考欄' . $user->name,
                            'applied_by' => $user->id,
                        ]);
            BreakTimeChange::factory()->create([
                        'attendance_change_id' => $attendanceChange->id,
                        'clock_in' => $breakTimeChange->clock_in,
                        'clock_out' => $now->copy()->startOfMonth()->subMinutes(15)->format('Y-m-d H:i:s'),
            ]);

        }
        // 1. 管理者ユーザーにログインをする
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        session([
            'user_id' => $admin->id,
            'login_form' => LoginForm::ADMIN->value,
        ]);
        $this->actingAs($admin);

        // 2. 修正申請一覧ページを開き、承認待ちタブを開く
        // 全ユーザーの未承認の修正申請が表示される
        $response = $this->actingAs($admin)->get(route('application.index', [
                    'mode' => ApprovalStatus::PENDING->value
                ]));
        $response->assertStatus(200);
        $count = 0;
        foreach ($users as $user) {
            $count++;
            $attendanceChange = AttendanceChange::where('user_id', $user->id)->first();
            $response->assertSeeInOrder([
                        ApprovalStatus::PENDING->label(),
                        $user->name,
                        Carbon::parse($attendanceChange->work_date)->format('Y/m/d'),
                        '備考欄' . $user->name,
                        ($attendanceChange->applied_at)->format('Y/m/d'),
                    ]);
        }
        $this->assertEquals(count($users), $count);
    }

    /**
     * 承認済みの修正申請が全て表示されている
     */
    #[Test]
    public function allApprovedApplicantsAreDisplayed()
    {
        // 1. 管理者ユーザーにログインをする
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        session([
            'user_id' => $admin->id,
            'login_form' => LoginForm::ADMIN->value,
        ]);
        $this->actingAs($admin);

        $users = User::factory(3)->create();
        $now = now();
        foreach ($users as $user) {
            $attendance = Attendance::factory()->create([
                'user_id' => $user->id,
                'work_date' => $now->copy()->startOfMonth()->format('Y-m-d'),
                'clock_in' => $now->copy()->startOfMonth()->format('Y-m-d H:i:s'),
                'clock_out' => $now->copy()->startOfMonth()->addHour()->format('Y-m-d H:i:s'),
            ]);
            $breakTimeChange = BreakTime::factory()->create([
                        'attendance_id' => $attendance->id,
                        'clock_in' => $now->copy()->startOfMonth()->subMinutes(45)->format('Y-m-d H:i:s'),
                        'clock_out' => $now->copy()->startOfMonth()->subMinutes(30)->format('Y-m-d H:i:s'),
            ]);
            $attendanceChange = AttendanceChange::factory()->create([
                            'attendance_id' => $attendance->id,
                            'user_id' => $user->id,
                            'work_date' => $attendance->work_date,
                            'clock_in' => $attendance->clock_in,
                            'clock_out' => $now->copy()->startOfMonth()->addHour($user->id)->format('Y-m-d H:i:s'),
                            'note' => '備考欄備考欄' . $user->name,
                            'applied_by' => $user->id,
                        ]);
            BreakTimeChange::factory()->create([
                        'attendance_change_id' => $attendanceChange->id,
                        'clock_in' => $breakTimeChange->clock_in,
                        'clock_out' => $now->copy()->startOfMonth()->subMinutes(15)->format('Y-m-d H:i:s'),
            ]);

            // 承認する
            $attendanceUpdateService = app(AttendanceUpdateService::class);
            $attendanceUpdateService->approveAttendance($attendanceChange->id, $admin->id);
        }

        // 2. 修正申請一覧ページを開き、承認済みのタブを開く
        // 全ユーザーの承認済みの修正申請が表示される
        $response = $this->actingAs($admin)->get(route('application.index', [
                    'mode' => ApprovalStatus::APPROVED->value
                ]));
        $response->assertStatus(200);

        $count = 0;
        foreach ($users as $user) {
            $count++;
            $attendanceChange = AttendanceChange::where('user_id', $user->id)->first();
            $response->assertSeeInOrder([
                        ApprovalStatus::APPROVED->label(),
                        $user->name,
                        Carbon::parse($attendanceChange->work_date)->format('Y/m/d'),
                        '備考欄備考欄' . $user->name,
                        ($attendanceChange->applied_at)->format('Y/m/d'),
                    ]);
        }
        $this->assertEquals(count($users), $count);
    }

    /**
     * 修正申請の詳細内容が正しく表示されている
     */
    #[Test]
    public function ApplicantDetailIsDisplayedAccurately()
    {
        $user = User::factory()->create();
        $now = now();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $now->copy()->startOfMonth()->format('Y-m-d'),
            'clock_in' => $now->copy()->startOfMonth()->format('Y-m-d H:i:s'),
            'clock_out' => $now->copy()->startOfMonth()->addHours(8)->format('Y-m-d H:i:s'),
        ]);
        $attendanceChange = AttendanceChange::factory()->create([
                        'attendance_id' => $attendance->id,
                        'user_id' => $user->id,
                        'work_date' => $attendance->work_date,
                        'clock_in' => $now->copy()->startOfMonth()->addHours(12)->format('Y-m-d H:i:s'),
                        'clock_out' => $now->copy()->startOfMonth()->addHour(20)->format('Y-m-d H:i:s'),
                        'note' => '備考欄備考欄備考欄' . $user->name,
                        'applied_by' => $user->id,
                    ]);
        BreakTimeChange::factory()->create([
                    'attendance_change_id' => $attendanceChange->id,
                    'clock_in' => $now->copy()->startOfMonth()->addHour(16)->format('Y-m-d H:i:s'),
                    'clock_out' => $now->copy()->startOfMonth()->addHour(17.5)->format('Y-m-d H:i:s'),
        ]);

        // 1. 管理者ユーザーにログインをする
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        session([
            'user_id' => $admin->id,
            'login_form' => LoginForm::ADMIN->value,
        ]);
        $this->actingAs($admin);

        // 2. 修正申請の詳細画面を開く
        // 申請内容が正しく表示されている
        $response = $this->actingAs($admin)->get(route('admin.approval.show', [
                    'attendance_correct_request_id' => $attendanceChange->id
                ]));
        $response->assertStatus(200);

        $response->assertSeeInOrder([
                    $user->name,
                    Carbon::parse($attendance->work_date)->format('Y年'),
                    Carbon::parse($attendance->work_date)->format('n月'),
                    Carbon::parse($attendance->work_date)->format('j日'),
                    $now->copy()->startOfMonth()->addHours(12)->format('H:i'),
                    $now->copy()->startOfMonth()->addHour(20)->format('H:i'),
                    $now->copy()->startOfMonth()->addHour(16)->format('H:i'),
                    $now->copy()->startOfMonth()->addHour(17.5)->format('H:i'),
                    '備考欄備考欄備考欄' . $user->name,
                ]);
    }

    /**
     * 修正申請の承認処理が正しく行われる
     */
    #[Test]
    public function approvalProcessingIsWorking()
    {
        $user = User::factory()->create();
        $now = now();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $now->copy()->startOfMonth()->format('Y-m-d'),
            'clock_in' => $now->copy()->startOfMonth()->format('Y-m-d H:i:s'),
            'clock_out' => $now->copy()->startOfMonth()->addHours(8)->format('Y-m-d H:i:s'),
        ]);
        $attendanceChange = AttendanceChange::factory()->create([
                        'attendance_id' => $attendance->id,
                        'user_id' => $user->id,
                        'work_date' => $attendance->work_date,
                        'clock_in' => $now->copy()->startOfMonth()->addHours(12)->format('Y-m-d H:i:s'),
                        'clock_out' => $now->copy()->startOfMonth()->addHour(20)->format('Y-m-d H:i:s'),
                        'note' => '備考欄備考欄備考欄備考欄' . $user->name,
                        'applied_by' => $user->id,
                    ]);
        BreakTimeChange::factory()->create([
                    'attendance_change_id' => $attendanceChange->id,
                    'clock_in' => $now->copy()->startOfMonth()->addHour(16)->format('Y-m-d H:i:s'),
                    'clock_out' => $now->copy()->startOfMonth()->addHour(17.5)->format('Y-m-d H:i:s'),
        ]);

        // 1. 管理者ユーザーにログインをする
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        session([
            'user_id' => $admin->id,
            'login_form' => LoginForm::ADMIN->value,
        ]);
        $this->actingAs($admin);

        // 2. 修正申請の詳細画面で「承認」ボタンを押す
        // 修正申請が承認され、勤怠情報が更新される
        $response = $this->actingAs($admin)->post(route('admin.approve', [
                    'attendance_change_id' => $attendanceChange->id,
                ]));

        // 承認待ちには存在しない
        $response = $this->actingAs($admin)->get(route('application.index', [
                    'mode' => ApprovalStatus::PENDING->value
                ]));
        $response->assertStatus(200);
        $response->assertDontSee($user->name);

        // 承認済みに存在する
        $response = $this->actingAs($admin)->get(route('application.index', [
                        'mode' => ApprovalStatus::APPROVED->value
                    ]));
        $response->assertStatus(200);
        $response->assertSeeInOrder([
                    ApprovalStatus::APPROVED->label(),
                    $user->name,
                    Carbon::parse($attendanceChange->work_date)->format('Y/m/d'),
                    '備考欄備考欄備考欄備考欄' . $user->name,
                    ($attendanceChange->applied_at)->format('Y/m/d'),
                ]);
        // 修正申請詳細画面で「承認済み」表示されている
        $response = $this->actingAs($admin)->get(route('admin.approval.show', [
                            'attendance_correct_request_id' => $attendanceChange->id
                        ]));
        $response->assertStatus(200);
        $response->assertSee('承認済み</button>', false);
    }
}

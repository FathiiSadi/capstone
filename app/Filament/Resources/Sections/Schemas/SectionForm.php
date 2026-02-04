<?php

namespace App\Filament\Resources\Sections\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;

class SectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('course_id')
                    ->relationship('course', 'name')
                    ->required()
                    ->live()
                    ->disabledOn('edit'),
                TextInput::make('section_number')
                    ->label('Section Number')
                    ->placeholder('e.g. S1, S2')
                    ->maxLength(20)
                    ->disabledOn('edit'),
                Select::make('semester_id')
                    ->relationship('semester', 'name')
                    ->required()
                    ->disabledOn('edit'),
                Select::make('instructor_id')
                    ->label('Instructor')
                    ->options(function () {
                        return \App\Models\Instructor::join('users', 'instructors.user_id', '=', 'users.id')
                            ->pluck('users.name', 'instructors.id');
                    })
                    ->searchable()
                    ->placeholder('Select Instructor (Optional)')
                    ->disabledOn('edit')
                    ->live()
                    ->afterStateUpdated(fn($set) => $set('confirm_department_mismatch', false))
                    ->hint(function ($get) {
                        $instructorId = $get('instructor_id');
                        if (!$instructorId) {
                            return null;
                        }
                        $instructor = \App\Models\Instructor::find($instructorId);
                        if (!$instructor)
                            return null;

                        // Calculate Load
                        $currentLoad = $instructor->sections()
                            ->where('semester_id', $get('semester_id'))
                            ->where('status', '!=', 'Closed')
                            ->get()
                            ->sum(fn($section) => $section->course->credits);

                        $minLoad = $instructor->min_credits;

                        return "Current Load: {$currentLoad} Credits (Min: {$minLoad})";
                    })
                    ->hintColor('info')
                    ->rule(function ($get) {
                        return function (string $attribute, $value, \Closure $fail) use ($get) {
                            $instructorId = $value;
                            $semesterId = $get('semester_id');
                            $days = $get('days');
                            $startTime = $get('start_time');
                            $endTime = $get('end_time');

                            if (!$instructorId || !$semesterId || !$days || !$startTime || !$endTime) {
                                return;
                            }

                            // Check for overlapping sections
                            $conflictingSection = \App\Models\Section::where('instructor_id', $instructorId)
                                ->where('semester_id', $semesterId)
                                ->where('id', '!=', $get('id')) // Ignore current record if editing
                                ->where(function ($query) use ($days) {
                                foreach ($days as $day) {
                                    $query->orWhereJsonContains('days', $day);
                                }
                            })
                                ->where(function ($query) use ($startTime, $endTime) {
                                $query->where(function ($q) use ($startTime, $endTime) {
                                    $q->where('start_time', '<', $endTime)
                                        ->where('end_time', '>', $startTime);
                                });
                            })
                                ->first();

                            if ($conflictingSection) {
                                $fail("Instructor has a scheduling conflict with {$conflictingSection->course->name} ({$conflictingSection->start_time} - {$conflictingSection->end_time})");
                            }
                        };
                    }),
                \Filament\Forms\Components\Checkbox::make('confirm_department_mismatch')
                    ->label('I confirm assigning this instructor to a course outside their department.')
                    ->visible(function ($get) {
                        $instructorId = $get('instructor_id');
                        $courseId = $get('course_id');
                        if (!$instructorId || !$courseId) {
                            return false;
                        }
                        $instructor = \App\Models\Instructor::find($instructorId);
                        $course = \App\Models\Course::find($courseId);

                        return $instructor && $course && $instructor->user->department_id !== $course->department_id;
                    })
                    ->required(function ($get) {
                        $instructorId = $get('instructor_id');
                        $courseId = $get('course_id');
                        if (!$instructorId || !$courseId) {
                            return false;
                        }
                        $instructor = \App\Models\Instructor::find($instructorId);
                        $course = \App\Models\Course::find($courseId);

                        return $instructor && $course && $instructor->user->department_id !== $course->department_id;
                    }),
                Select::make('days')
                    ->options([
                        'Sunday' => 'Sunday',
                        'Monday' => 'Monday',
                        'Tuesday' => 'Tuesday',
                        'Wednesday' => 'Wednesday',
                        'Thursday' => 'Thursday',
                        'Saturday' => 'Saturday',
                    ])
                    ->multiple()
                    ->required(fn($get) => !\App\Models\Course::find($get('course_id'))?->office_hours)
                    ->disabledOn('edit'),
                TimePicker::make('start_time')
                    ->required(fn($get) => !\App\Models\Course::find($get('course_id'))?->office_hours)
                    ->disabledOn('edit')
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        if (!$state) {
                            return;
                        }

                        $course = \App\Models\Course::find($get('course_id'));
                        if (!$course) {
                            return;
                        }

                        $duration = ($course->hours ?? 3.0) / 2.0;
                        $endTime = \Carbon\Carbon::parse($state)
                            ->addMinutes((int) ($duration * 60))
                            ->format('H:i:s');

                        $set('end_time', $endTime);
                    }),
                TimePicker::make('end_time')
                    ->required(fn($get) => !\App\Models\Course::find($get('course_id'))?->office_hours)
                    ->disabledOn('edit'),
                Select::make('room_id')
                    ->relationship('room', 'name')
                    ->label('Room')
                    ->placeholder('Select Room (Optional)')
                    ->preload()
                    ->searchable()
                    ->disabledOn('edit'),
                Select::make('status')
                    ->options([
                        'Active' => 'Active',
                        'Allocated' => 'Allocated',
                        'Underloaded' => 'Underloaded',
                        'Admin Override' => 'Admin Override',
                        'Inactive' => 'Inactive',
                        'Closed' => 'Closed',
                    ])
                    ->default('Active')
                    ->required(),
            ]);
    }
}

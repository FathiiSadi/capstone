<?php

namespace App;

enum InstructorPosition: string
{
    case Instructor = 'instructor';
    case Dean = 'dean';
    case POP = 'professor of practice';
    case Lecturer = 'lecturer';
    case Doctor = 'doctor';
    case HOD = 'head of department';

    case TA = 'teacher assistant';

}

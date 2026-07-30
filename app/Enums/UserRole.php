<?php

namespace App\Enums;

enum UserRole: string
{
    case STUDENT = 'student';
    case UNIVERSITY = 'university';
    case ADMIN = 'admin';
}
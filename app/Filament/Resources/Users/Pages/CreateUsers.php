<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUsers extends CreateRecord{
    protected static string $resource = UserResource::class;
}

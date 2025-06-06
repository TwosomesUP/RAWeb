<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\UnrankedUser;
use App\Models\User;
use Carbon\Carbon;
use Filament\Resources\Pages\EditRecord;

class Edit extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var User $record */
        $record = $this->record;

        if ((bool) $record->Untracked !== $data['Untracked']) {
            $data['unranked_at'] = $data['Untracked'] ? Carbon::now() : null;

            if ($data['unranked_at'] !== null) {
                UnrankedUser::firstOrCreate(['user_id' => $record->id]);
            } else {
                UnrankedUser::where('user_id', $record->id)->delete();
            }
        }

        return $data;
    }
}

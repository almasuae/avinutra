<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Pages;

use DateTimeZone;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use LiteCrm\Contracts\CrmUser;
use LiteCrm\Models\Lookup;
use LogicException;
use SensitiveParameter;

/**
 * Filament's profile page plus the CRM profile: job title, location, time zone,
 * phone numbers and digest preference. MFA management is added by Filament.
 */
class EditProfile extends BaseEditProfile
{
    public const PROFILE_FIELDS = ['job_title', 'city', 'country', 'time_zone', 'phone', 'whatsapp', 'receives_digest'];

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                Section::make(__('lite-crm::users.sections.profile'))
                    ->schema(static::profileFormComponents())
                    ->columns(2),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                $this->getCurrentPasswordFormComponent(),
            ]);
    }

    /**
     * Profile fields a user may edit themselves (territory is set by Admins).
     *
     * @return array<int, Field>
     */
    public static function profileFormComponents(): array
    {
        return [
            TextInput::make('profile.job_title')->label(__('lite-crm::users.fields.job_title'))->maxLength(255),
            TextInput::make('profile.city')->label(__('lite-crm::users.fields.city'))->maxLength(255),
            TextInput::make('profile.country')->label(__('lite-crm::users.fields.country'))->maxLength(255),
            Select::make('profile.time_zone')
                ->label(__('lite-crm::users.fields.time_zone'))
                ->options(static::timeZoneOptions())
                ->searchable()
                ->required()
                ->in(DateTimeZone::listIdentifiers()),
            TextInput::make('profile.phone')->label(__('lite-crm::users.fields.phone'))->tel()->maxLength(50),
            TextInput::make('profile.whatsapp')->label(__('lite-crm::users.fields.whatsapp'))->tel()->maxLength(50),
            Toggle::make('profile.receives_digest')->label(__('lite-crm::users.fields.receives_digest')),
        ];
    }

    /**
     * @throws LogicException when the host user model does not implement CrmUser
     */
    public static function crmUser(Model $user): Model&CrmUser
    {
        if (! $user instanceof CrmUser) {
            throw new LogicException($user::class.' must implement '.CrmUser::class.'.');
        }

        return $user;
    }

    /**
     * @return array<string, string>
     */
    public static function timeZoneOptions(): array
    {
        $zones = DateTimeZone::listIdentifiers();

        return array_combine($zones, $zones);
    }

    /**
     * @return array<int, string>
     */
    public static function territoryOptions(): array
    {
        /** @var array<int, string> $options */
        $options = Lookup::options('territory');

        return $options;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data = parent::mutateFormDataBeforeFill($data);
        $profile = static::crmUser($this->getUser())->getCrmProfile();

        $data['profile'] = $profile->only(self::PROFILE_FIELDS);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, #[SensitiveParameter] array $data): Model
    {
        /** @var array<string, mixed> $profileData */
        $profileData = $data['profile'] ?? [];
        unset($data['profile']);

        $profile = static::crmUser($record)->getCrmProfile();
        $profile->fill(array_intersect_key($profileData, array_flip(self::PROFILE_FIELDS)));
        $profile->save();

        return parent::handleRecordUpdate($record, $data);
    }
}

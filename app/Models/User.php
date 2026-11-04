<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_USER = 'user';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'telephone',
        'ville',
        'locale',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function favoris()
    {
        return $this->belongsToMany(Article::class, 'article_user')->withTimestamps();
    }

    public function aiConversations()
    {
        return $this->hasMany(AiConversation::class);
    }

    public function eventBlueprints(): HasMany
    {
        return $this->hasMany(EventBlueprint::class);
    }

    /**
     * Initiales affichées dans les avatars
     * (ex. "Hamza Mbarki" => "HM").
     */
    public function getInitialesAttribute(): string
    {
        return collect(explode(' ', $this->name))
            ->filter()
            ->take(2)
            ->map(fn ($mot) => mb_strtoupper(mb_substr($mot, 0, 1)))
            ->implode('');
    }

    /**
     * Réclamations créées par cet utilisateur.
     */
    public function reclamations()
    {
        return $this->hasMany(Reclamation::class, 'user_id');
    }

    /**
     * Réponses aux réclamations écrites par cet utilisateur.
     */
    public function reponsesReclamations()
    {
        return $this->hasMany(ReponseReclamation::class, 'user_id');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'user_id');
    }

    public function participations()
    {
        return $this->hasMany(Participation::class, 'user_id');
    }

    public function aiFeatureRuns()
    {
        return $this->hasMany(AiFeatureRun::class);
    }

    public function aiFeedback()
    {
        return $this->hasMany(AiFeedback::class);
    }

    public function notificationPreference()
    {
        return $this->hasOne(NotificationPreference::class);
    }

    public function smartNotifications()
    {
        return $this->hasMany(SmartNotification::class);
    }
}

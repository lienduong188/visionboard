<?php

namespace App\Policies;

use App\Models\ThemeWord;
use App\Models\User;

class ThemeWordPolicy
{
    /**
     * Determine whether the user can update the theme word.
     */
    public function update(User $user, ThemeWord $themeWord): bool
    {
        return $user->id === $themeWord->user_id;
    }

    /**
     * Determine whether the user can delete the theme word.
     */
    public function delete(User $user, ThemeWord $themeWord): bool
    {
        return $user->id === $themeWord->user_id;
    }
}

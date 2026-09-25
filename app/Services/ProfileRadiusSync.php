<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\Radgroupcheck;
use App\Models\Radgroupreply;

class ProfileRadiusSync
{
    /**
     * Mirror a profile's settings into radgroupcheck/radgroupreply so
     * FreeRADIUS applies the right limits to every user in this group.
     */
    public function sync(Profile $profile, ?string $oldGroupName = null): void
    {
        if ($oldGroupName && $oldGroupName !== $profile->group_name) {
            Radgroupcheck::where('groupname', $oldGroupName)->delete();
            Radgroupreply::where('groupname', $oldGroupName)->delete();
        }

        $groupName = $profile->group_name;

        Radgroupcheck::where('groupname', $groupName)->delete();
        Radgroupreply::where('groupname', $groupName)->delete();

        Radgroupcheck::create([
            'groupname' => $groupName,
            'attribute' => 'Simultaneous-Use',
            'op' => ':=',
            'value' => (string) $profile->simultaneous_use,
        ]);

        $reply = [];

        if ($profile->rate_up && $profile->rate_down) {
            $reply['Mikrotik-Rate-Limit'] = "{$profile->rate_up}/{$profile->rate_down}";
        }

        if ($profile->session_timeout) {
            $reply['Session-Timeout'] = (string) $profile->session_timeout;
        }

        if ($profile->idle_timeout) {
            $reply['Idle-Timeout'] = (string) $profile->idle_timeout;
        }

        if ($profile->mikrotik_group) {
            $reply['Mikrotik-Group'] = $profile->mikrotik_group;
        }

        foreach ($reply as $attribute => $value) {
            Radgroupreply::create([
                'groupname' => $groupName,
                'attribute' => $attribute,
                'op' => ':=',
                'value' => $value,
            ]);
        }
    }

    public function removeGroup(string $groupName): void
    {
        Radgroupcheck::where('groupname', $groupName)->delete();
        Radgroupreply::where('groupname', $groupName)->delete();
    }
}

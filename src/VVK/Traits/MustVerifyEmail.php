<?php

namespace VVK\Traits;

use Illuminate\Auth\Notifications;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
trait MustVerifyEmail
{
    /**
     * Determine if the user has verified their email address.
     *
     * @return bool
     */
    public function hasVerifiedEmail()
    {
        return ! is_null($this->getEmailVerifiedAt());
    }

    /**
     * Mark the given user's email as verified.
     *
     * @return bool
     */
    public function markEmailAsVerified()
    {
        $this->setEmailVerifiedAt(now());
        $em = app('em');
        $em->flush();
    }

    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new Notifications\VerifyEmail);
    }

    /**
     * Get the email address that should be used for verification.
     *
     * @return string
     */
    public function getEmailForVerification()
    {
        return $this->getEmail();
    }
}

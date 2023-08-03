<?php

declare(strict_types=1);

/**
 * @file classes/invitation/invitations/BaseInvitation.php
 *
 * Copyright (c) 2014-2023 Simon Fraser University
 * Copyright (c) 2000-2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class BaseInvitation
 *
 * @brief Abstract class for all Invitations
 */

namespace PKP\invitation\invitations;

use APP\core\Application;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use PKP\config\Config;
use PKP\context\Context;
use PKP\facades\Repo;
use PKP\invitation\invitations\enums\InvitationStatus;
use PKP\invitation\models\Invitation;
use PKP\pages\invitation\PKPInvitationHandler;
use PKP\security\Validation;
use Symfony\Component\Mailer\Exception\TransportException;

abstract class BaseInvitation
{
    use SerializesModels;

    public const DEFAULT_EXPIRY_DAYS = 3;

    /**
     * The name of the class name of the specific invitation
     */
    public string $className;
    private string $keyHash;
    public string $key;
    public DateTime $expirationDate;
    // private Invitation $invitationModel;

    protected ?Mailable $mailable = null;
    protected ?Context $context = null;

    public function __construct(
        public ?int $userId,
        public ?string $email,
        public int $contextId,
        public ?int $assocId,
        public ?int $expiryDays = null
    ) {
        $usedExpiryDays = ($expiryDays) ? $expiryDays : Config::getVar('invitations', 'expiration_days', self::DEFAULT_EXPIRY_DAYS);
        $this->expirationDate = Carbon::now()->addDays($usedExpiryDays)->toDateTime();
        $this->className = get_class($this);
    }

    public function getPayload()
    {
        $vars = get_object_vars($this);

        foreach ($this->getExcludedPayloadVariables() as $excludedPayloadVariable) {
            unset($vars[$excludedPayloadVariable]);
        }

        return $vars;
    }

    public function invitationMarkStatus(InvitationStatus $status)
    {
        $invitation = Repo::invitation()
            ->getByKeyHash($this->keyHash);

        if (is_null($invitation)) {
            throw new Exception('This invitation was not found');
        }

        switch ($status) {
            case InvitationStatus::ACCEPTED:
                $invitation->markInvitationAsAccepted();
                break;
            case InvitationStatus::DECLINED:
                $invitation->markInvitationAsDeclined();
                break;
            case InvitationStatus::EXPIRED:
                $invitation->markInvitationAsExpired();
                break;
            case InvitationStatus::CANCELLED:
                $invitation->markInvitationAsCanceled();
                break;
            default:
                throw new Exception('Invalid Invitation type');
        }
    }

    public function invitationAcceptHandle(): void
    {
        $this->invitationMarkStatus(InvitationStatus::ACCEPTED);
    }
    public function invitationDeclineHandle(): void
    {
        $this->invitationMarkStatus(InvitationStatus::DECLINED);
    }

    abstract public function getInvitationMailable(): ?Mailable;
    abstract public function preDispatchActions(): bool;

    public function getAcceptInvitationUrl(): string
    {
        $request = Application::get()->getRequest();
        return $request->getDispatcher()
            ->url(
                $request,
                Application::ROUTE_PAGE,
                $request->getContext()->getPath(),
                PKPInvitationHandler::REPLY_PAGE,
                PKPInvitationHandler::REPLY_OP_ACCEPT,
                null,
                [
                    'key' => $this->key,
                ]
            );
    }
    public function getDeclineInvitationUrl(): string
    {
        $request = Application::get()->getRequest();
        return $request->getDispatcher()
            ->url(
                $request,
                Application::ROUTE_PAGE,
                $request->getContext()->getPath(),
                PKPInvitationHandler::REPLY_PAGE,
                PKPInvitationHandler::REPLY_OP_DECLINE,
                null,
                [
                    'key' => $this->key,
                ]
            );
    }

    public function dispatch(bool $sendEmail = false): bool
    {
        $request = Application::get()->getRequest();
        $user = $request->getUser();

        // Need to return error messages also?
        if (!$this->preDispatchActions()) {
            return false;
        }

        if (!isset($this->keyHash)) {
            if (!isset($this->key)) {
                $this->key = Validation::generatePassword();
            }

            $this->keyHash = md5($this->key);
        }

        $invitationModelData = [
            'key_hash' => $this->keyHash,
            'user_id' => $this->userId,
            'assoc_id' => $this->assocId,
            'expiry_date' => $this->expirationDate->getTimestamp(),
            'payload' => $this->getPayload(),
            'created_at' => Carbon::now()->timestamp,
            'updated_at' => Carbon::now()->timestamp,
            'status' => InvitationStatus::PENDING,
            'class_name' => $this->className,
            'email' => $this->email,
            'context_id' => $this->contextId
        ];

        $invitationModel = Invitation::create($invitationModelData);

        $mailable = $this->getInvitationMailable();

        if ($sendEmail && isset($mailable)) {
            try {
                Mail::to($this->email)
                    ->send($mailable);

            } catch (TransportException $e) {
                trigger_error('Failed to send email invitation: ' . $e->getMessage(), E_USER_ERROR);
            }
        }

        return true;
    }

    public function isKeyValid(string $key): bool
    {
        $keyHash = md5($key);

        return $keyHash == $this->keyHash;
    }

    public function getExcludedPayloadVariables(): array
    {
        return [
            'mailable',
            'expiryDays',
            'context',
            'userId',
            'key',
            'keyHash',
            'expirationDate',
            'className'
        ];
    }

    public function setMailable(Mailable $mailable): void
    {
        $this->mailable = $mailable;
    }

    public function setKeyHash(string $keyHash): void
    {
        $this->keyHash = $keyHash;
    }

    public function setExpirationDate(Carbon $expirationDate): void
    {
        $this->expirationDate = $expirationDate;
    }

    public function setInvitationModel(Invitation $invitationModel)
    {
        $this->keyHash = $invitationModel->keyHash;
        $this->expirationDate = $invitationModel->expiryDate;
    }
}

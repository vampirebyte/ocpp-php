<?php

declare(strict_types=1);

namespace SolutionForest\OcppPhp\Ocpp\v16\Interfaces;

interface OcppServiceInterface
{
    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleAuthorize(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleBootNotification(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleCancelReservation(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleCertificateSigned(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleChangeAvailability(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleChangeConfiguration(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleClearCache(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleClearChargingProfile(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleDataTransfer(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleDeleteCertificate(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleDiagnosticsStatusNotification(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleExtendedTriggerMessage(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleFirmwareStatusNotification(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleGetCompositeSchedule(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleGetConfiguration(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleGetDiagnostics(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleGetInstalledCertificateIds(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleGetLocalListVersion(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleGetLog(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleHeartbeat(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleInstallCertificate(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleLogStatusNotification(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleMeterValues(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleRemoteStartTransaction(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleRemoteStopTransaction(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleReserveNow(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleReset(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleSecurityEventNotification(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleSendLocalList(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleSetChargingProfile(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleSignCertificate(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleSignedFirmwareStatusNotification(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleSignedUpdateFirmware(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleStartTransaction(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleStatusNotification(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleStopTransaction(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleTriggerMessage(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleUnlockConnector(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleUpdateFirmware(array $message): array;
}

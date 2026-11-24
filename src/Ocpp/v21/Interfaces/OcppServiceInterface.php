<?php

declare(strict_types=1);

namespace SolutionForest\OcppPhp\Ocpp\v21\Interfaces;

interface OcppServiceInterface
{
    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleAFRRSignal(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleAdjustPeriodicEventStream(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleAuthorize(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleBatterySwap(array $message): array;

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
    public static function handleChangeTransactionTariff(array $message): array;

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
    public static function handleClearDERControl(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleClearDisplayMessage(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleClearTariffs(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleClearVariableMonitoring(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleClearedChargingLimit(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleClosePeriodicEventStream(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleCostUpdated(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleCustomerInformation(array $message): array;

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
    public static function handleFirmwareStatusNotification(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleGet15118EVCertificate(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleGetBaseReport(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleGetCertificateChainStatus(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleGetCertificateStatus(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleGetChargingProfiles(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleGetCompositeSchedule(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleGetDERControl(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleGetDisplayMessages(array $message): array;

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
    public static function handleGetMonitoringReport(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleGetPeriodicEventStream(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleGetReport(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleGetTariffs(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleGetTransactionStatus(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleGetVariables(array $message): array;

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
    public static function handleNotifyAllowedEnergyTransfer(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleNotifyChargingLimit(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleNotifyCustomerInformation(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleNotifyDERAlarm(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleNotifyDERStartStop(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleNotifyDisplayMessages(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleNotifyEVChargingNeeds(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleNotifyEVChargingSchedule(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleNotifyEvent(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleNotifyMonitoringReport(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleNotifyPeriodicEventStream(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleNotifyPriorityCharging(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleNotifyReport(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleNotifySettlement(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleNotifyWebPaymentStarted(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleOpenPeriodicEventStream(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handlePublishFirmware(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handlePublishFirmwareStatusNotification(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handlePullDynamicScheduleUpdate(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleReportChargingProfiles(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleReportDERControl(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleRequestBatterySwap(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleRequestStartTransaction(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleRequestStopTransaction(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleReservationStatusUpdate(array $message): array;

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
    public static function handleSetDERControl(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleSetDefaultTariff(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleSetDisplayMessage(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleSetMonitoringBase(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleSetMonitoringLevel(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleSetNetworkProfile(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleSetVariableMonitoring(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleSetVariables(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleSignCertificate(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleStatusNotification(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleTransactionEvent(array $message): array;

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
    public static function handleUnpublishFirmware(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleUpdateDynamicSchedule(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleUpdateFirmware(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleUsePriorityCharging(array $message): array;

    /**
     * @param array<mixed> $message
     * @return array<mixed>
     */
    public static function handleVatNumberValidation(array $message): array;
}

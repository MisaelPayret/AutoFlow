<?php

require_once dirname(__DIR__) . '/Database/Database.php';
require_once dirname(__DIR__) . '/Core/View.php';
require_once dirname(__DIR__) . '/Model/VehicleModel.php';

/**
 * Controlador más complejo del sistema: administra catálogo, galería y página
 * pública de vehículos.
 *
 * Además de CRUD clásico, maneja carga de imágenes, reordenamientos y vistas de
 * detalle/compartir, centralizando todas las reglas en un único lugar.
 */
class VehicleController
{
    private Database $database;
    private VehicleModel $vehicleModel;
    private array $statusOptions = [];
    private array $transmissionOptions = [];
    private array $fuelOptions = [];

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->database = new Database();
        $this->vehicleModel = new VehicleModel($this->database->getConnection());
        $this->statusOptions = $this->vehicleModel->getStatusOptions();
        $this->transmissionOptions = $this->vehicleModel->getTransmissionOptions();
        $this->fuelOptions = $this->vehicleModel->getFuelOptions();
    }

    /**
     * Lista vehículos aplicando filtros y resume estado de cargas recientes.
     */
    public function index(): void
    {
        $this->ensureAuthenticated();

        $search = trim((string)($_GET['search'] ?? ''));
        $status = trim((string)($_GET['status'] ?? ''));
        $status = in_array($status, $this->statusOptions, true) ? $status : '';
        $vehicles = $this->vehicleModel->search([
            'search' => $search,
            'status' => $status,
        ]);
        $statusCounts = $this->vehicleModel->countByStatus();

        $flashMessage = $_SESSION['vehicle_flash'] ?? null;
        unset($_SESSION['vehicle_flash']);

        $uploadWarnings = $this->consumeUploadWarnings('list');
        $uploadSummary = $this->consumeUploadSummary('list');

        View::render('Vehicles/Index.php', [
            'vehicles' => $vehicles,
            'statusCounts' => $statusCounts,
            'statusOptions' => $this->statusOptions,
            'search' => $search,
            'status' => $status,
            'flashMessage' => $flashMessage,
            'uploadWarnings' => $uploadWarnings,
            'uploadSummary' => $uploadSummary,
        ]);
    }

    /**
     * Muestra el formulario de alta con datos por defecto y feedback de uploads.
     */
    public function create(): void
    {
        $this->ensureAuthenticated();
        [$formData, $formErrors] = $this->retrieveFormState();

        if (empty($formData)) {
            $formData = $this->vehicleModel->defaultData();
        }

        View::render('Vehicles/Form.php', [
            'isEdit' => false,
            'pageTitle' => 'Nuevo vehículo | AutoFlow',
            'bodyClass' => 'dashboard-page vehicles-page vehicle-form-page',
            'formData' => $formData,
            'formErrors' => $formErrors,
            'statusOptions' => $this->statusOptions,
            'transmissionOptions' => $this->transmissionOptions,
            'fuelOptions' => $this->fuelOptions,
            'vehicleImages' => [],
            'uploadWarnings' => $this->consumeUploadWarnings('form'),
            'uploadSummary' => $this->consumeUploadSummary('form'),
        ]);
    }

    /**
     * Crea un vehículo nuevo y procesa la primera tanda de imágenes.
     */
    public function store(): void
    {
        $this->ensureAuthenticated();
        $this->assertPostRequest();

        $formData = $this->vehicleModel->normalizeInput($_POST);
        $errors = $this->vehicleModel->validate($formData);

        if (!empty($errors)) {
            $this->rememberFormState($formData, $errors);
            $this->redirectToRoute('vehicles/create');
        }

        try {
            $vehicleId = $this->vehicleModel->create($formData);
        } catch (\PDOException $exception) {
            $this->handleVehicleDbException($exception, $formData, 'vehicles/create');
            return;
        }

        $uploadFeedback = $this->processVehicleImagesUpload($vehicleId);
        $this->flashUploadFeedback($uploadFeedback, ['list', 'form']);

        $_SESSION['vehicle_flash'] = 'Vehículo creado correctamente.';
        $this->redirectToRoute('vehicles/edit', ['id' => $vehicleId]);
    }

    /**
     * Abre el formulario en modo edición incluyendo galería existente.
     */
    public function edit(): void
    {
        $this->ensureAuthenticated();

        $vehicleId = (int) ($_GET['id'] ?? 0);
        if ($vehicleId <= 0) {
            $_SESSION['vehicle_flash'] = 'Identificador inválido.';
            $this->redirectToRoute('vehicles');
        }

        $vehicle = $this->vehicleModel->find($vehicleId);
        if (!$vehicle) {
            $_SESSION['vehicle_flash'] = 'Vehículo no encontrado.';
            $this->redirectToRoute('vehicles');
        }

        [$oldData, $formErrors] = $this->retrieveFormState();
        if (!empty($oldData)) {
            $vehicle = array_merge($vehicle, $oldData);
        }

        View::render('Vehicles/Form.php', [
            'isEdit' => true,
            'pageTitle' => 'Editar vehículo | AutoFlow',
            'bodyClass' => 'dashboard-page vehicles-page vehicle-form-page',
            'formData' => $vehicle,
            'formErrors' => $formErrors,
            'statusOptions' => $this->statusOptions,
            'transmissionOptions' => $this->transmissionOptions,
            'fuelOptions' => $this->fuelOptions,
            'vehicleImages' => $this->vehicleModel->getImages($vehicleId),
            'uploadWarnings' => $this->consumeUploadWarnings('form'),
            'uploadSummary' => $this->consumeUploadSummary('form'),
        ]);
    }

    /**
     * Actualiza datos básicos, aplica cambios de galería y maneja reintentos de upload.
     */
    public function update(): void
    {
        $this->ensureAuthenticated();
        $this->assertPostRequest();

        $vehicleId = (int) ($_POST['id'] ?? 0);
        if ($vehicleId <= 0) {
            $this->redirectToRoute('vehicles');
        }

        $vehicle = $this->vehicleModel->find($vehicleId);
        if (!$vehicle) {
            $_SESSION['vehicle_flash'] = 'Vehículo no encontrado.';
            $this->redirectToRoute('vehicles');
        }

        $formData = $this->vehicleModel->normalizeInput($_POST, $vehicle);
        $errors = $this->vehicleModel->validate($formData);

        if (!empty($errors)) {
            $formData['id'] = $vehicleId;
            $this->rememberFormState($formData, $errors);
            $this->redirectToRoute('vehicles/edit', ['id' => $vehicleId]);
        }

        try {
            $this->vehicleModel->update($vehicleId, $formData);
        } catch (\PDOException $exception) {
            $this->handleVehicleDbException($exception, $formData, 'vehicles/edit', ['id' => $vehicleId]);
            return;
        }

        $this->applyGalleryEdits($vehicleId);

        $uploadFeedback = $this->processVehicleImagesUpload($vehicleId);
        $this->flashUploadFeedback($uploadFeedback, ['list', 'form']);

        $_SESSION['vehicle_flash'] = 'Vehículo actualizado correctamente.';
        $this->redirectToRoute('vehicles');
    }

    /**
     * Borra un vehículo y limpia los archivos asociados del disco.
     */
    public function delete(): void
    {
        $this->ensureAuthenticated();
        $this->assertPostRequest();

        $vehicleId = (int) ($_POST['id'] ?? 0);
        if ($vehicleId <= 0) {
            $this->redirectToRoute('vehicles');
        }

        $images = $this->vehicleModel->getImages($vehicleId);
        $this->vehicleModel->delete($vehicleId);

        foreach ($images as $image) {
            $this->removeImageFromDisk($image['storage_path'] ?? null);
        }

        $_SESSION['vehicle_flash'] = 'Vehículo eliminado.';
        $this->redirectToRoute('vehicles');
    }

    /**
     * Renderiza una vista detallada pensada para usuarios internos.
     */
    public function show(): void
    {
        $this->ensureAuthenticated();

        $vehicleId = (int) ($_GET['id'] ?? 0);
        if ($vehicleId <= 0) {
            $_SESSION['vehicle_flash'] = 'Identificador inválido.';
            $this->redirectToRoute('vehicles');
        }

        $vehicle = $this->vehicleModel->find($vehicleId);
        if (!$vehicle) {
            $_SESSION['vehicle_flash'] = 'Vehículo no encontrado.';
            $this->redirectToRoute('vehicles');
        }

        $vehicleImages = $this->vehicleModel->getImages($vehicleId);
        $coverImage = $vehicleImages[0]['storage_path'] ?? null;
        $imageCount = count($vehicleImages);

        View::render('Vehicles/Show.php', [
            'pageTitle' => sprintf('%s %s | AutoFlow', $vehicle['brand'] ?? 'Vehículo', $vehicle['model'] ?? ''),
            'bodyClass' => 'dashboard-page vehicles-page vehicle-detail-page',
            'vehicle' => $vehicle,
            'vehicleImages' => $vehicleImages,
            'coverImage' => $coverImage,
            'insights' => $this->vehicleModel->buildVehicleInsights($vehicleId),
            'imageCount' => $imageCount,
            'carouselMode' => $imageCount >= 4,
        ]);
    }

    /**
     * Construye la landing pública/marketing para un vehículo específico.
     */
    public function share(): void
    {
        $vehicleId = (int) ($_GET['id'] ?? 0);
        if ($vehicleId <= 0) {
            $this->renderSharePage([
                'shareError' => 'El vehículo solicitado no existe.',
            ]);
            return;
        }

        $vehicle = $this->vehicleModel->find($vehicleId);
        if (!$vehicle) {
            $this->renderSharePage([
                'shareError' => 'El vehículo solicitado no está disponible.',
            ]);
            return;
        }

        $vehicleImages = $this->vehicleModel->getImages($vehicleId);
        $coverImage = $vehicleImages[0]['storage_path'] ?? null;
        $vehicleName = trim((string) (($vehicle['brand'] ?? '') . ' ' . ($vehicle['model'] ?? '')));

        $this->renderSharePage([
            'vehicle' => $vehicle,
            'vehicleImages' => $vehicleImages,
            'heroImage' => $coverImage,
            'specs' => $this->buildPublicSpecs($vehicle),
            'perks' => $this->buildPublicPerks($vehicle),
            'vehicleDescription' => $this->buildPublicDescription($vehicle),
            'vehicleName' => $vehicleName !== '' ? $vehicleName : 'Vehículo AutoFlow',
        ]);
    }

    /**
     * Mezcla datos mínimos con defaults y renderiza la plantilla pública.
     */
    private function renderSharePage(array $data): void
    {
        $defaults = [
            'vehicle' => null,
            'vehicleImages' => [],
            'heroImage' => null,
            'specs' => [],
            'perks' => [],
            'vehicleDescription' => '',
            'vehicleName' => 'Vehículo AutoFlow',
            'shareError' => null,
            'ctaUrl' => 'mailto:ventas@autoflow.local',
            'customerPortalUrl' => 'index.php?route=auth/login',
        ];

        View::render('Public/VehicleShare.php', array_merge($defaults, $data));
    }

    /**
     * Genera especificaciones legibles para la tarjeta pública.
     */
    private function buildPublicSpecs(array $vehicle): array
    {
        $currency = '$' . number_format((float) ($vehicle['daily_rate'] ?? 0), 2);
        $capacityKg = number_format((int) ($vehicle['capacity_kg'] ?? 0));
        $passengers = (int) ($vehicle['passenger_capacity'] ?? 0);

        return [
            ['label' => 'Año', 'value' => (int) ($vehicle['year'] ?? date('Y'))],
            ['label' => 'Transmisión', 'value' => ucfirst((string) ($vehicle['transmission'] ?? 'manual'))],
            ['label' => 'Combustible', 'value' => ucfirst((string) ($vehicle['fuel_type'] ?? 'nafta'))],
            ['label' => 'Pasajeros', 'value' => $passengers > 0 ? $passengers . ' plazas' : 'Consultar'],
            ['label' => 'Capacidad de carga', 'value' => $capacityKg . ' kg'],
            ['label' => 'Tarifa diaria', 'value' => $currency . ' / día'],
        ];
    }

    /**
     * Lista beneficios resumidos según los datos más relevantes del vehículo.
     */
    private function buildPublicPerks(array $vehicle): array
    {
        $perks = [];
        $status = ucfirst((string) ($vehicle['status'] ?? 'Disponible'));
        $mileage = (int) ($vehicle['mileage_km'] ?? 0);
        $mileageCopy = $mileage > 0 ? number_format($mileage) . ' km reales' : 'Kilometraje a confirmar';
        $capacity = (int) ($vehicle['capacity_kg'] ?? 0);
        $passengers = (int) ($vehicle['passenger_capacity'] ?? 0);

        $perks[] = 'Estado actual: ' . $status;
        $perks[] = $mileageCopy;
        if ($passengers > 0) {
            $suffix = $passengers === 1 ? ' pasajero' : ' pasajeros';
            $perks[] = 'Configurado para ' . $passengers . $suffix;
        }
        if ($capacity > 0) {
            $perks[] = 'Carga útil aproximada de ' . number_format($capacity) . ' kg';
        }
        $perks[] = 'Documentación y mantenimiento listos para operar';
        $perks[] = 'Asistencia AutoFlow y monitoreo remoto incluidos (demo)';

        return $perks;
    }

    /**
     * Redacta un texto amigable aprovechando la información cargada.
     */
    private function buildPublicDescription(array $vehicle): string
    {
        if (!empty($vehicle['notes'])) {
            return (string) $vehicle['notes'];
        }

        $segments = [];
        $segments[] = sprintf('%s %s %s', $vehicle['brand'] ?? 'Vehículo', $vehicle['model'] ?? '', $vehicle['year'] ?? '');
        $segments[] = 'listo para integrarse a tu flota comercial.';
        $segments[] = 'Incluye plan de mantenimiento preventivo y soporte de nuestro equipo.';

        return trim(implode(' ', $segments));
    }

    /**
     * Interpreta inputs del formulario para borrar, reordenar y elegir portada.
     */
    private function applyGalleryEdits(int $vehicleId): void
    {
        if ($vehicleId <= 0) {
            return;
        }

        $deleteIds = $this->sanitizeIdArray($_POST['delete_image_ids'] ?? []);
        $positionsInput = $this->sanitizePositionsArray($_POST['image_positions'] ?? []);
        $coverId = isset($_POST['cover_image_id']) ? (int) $_POST['cover_image_id'] : 0;

        $stateFromJson = $this->parseGalleryStateFromJson();
        if (!empty($stateFromJson)) {
            if (!empty($stateFromJson['delete'])) {
                $deleteIds = array_values(array_unique(array_merge($deleteIds, $stateFromJson['delete'])));
            }

            if (!empty($stateFromJson['positions'])) {
                $positionsInput = array_replace($positionsInput, $stateFromJson['positions']);
            }

            if (!empty($stateFromJson['cover'])) {
                $coverId = (int) $stateFromJson['cover'];
            }
        }

        $currentImages = $this->vehicleModel->getImages($vehicleId);

        if (empty($currentImages)) {
            return;
        }

        $imagesById = [];
        foreach ($currentImages as $image) {
            $imagesById[(int) $image['id']] = $image;
        }

        if (!empty($deleteIds)) {
            foreach ($deleteIds as $deleteId) {
                if (!isset($imagesById[$deleteId])) {
                    continue;
                }

                $deletedPath = $this->vehicleModel->deleteImage($vehicleId, $deleteId);
                $this->removeImageFromDisk($deletedPath);
                unset($imagesById[$deleteId]);
            }
        }

        if (empty($imagesById)) {
            return;
        }

        $coverCandidate = ($coverId > 0 && isset($imagesById[$coverId])) ? $coverId : 0;

        $orderingPool = [];
        foreach ($imagesById as $imageId => $image) {
            if ($coverCandidate === $imageId) {
                continue;
            }

            $orderingPool[] = [
                'id' => $imageId,
                'order' => $positionsInput[$imageId] ?? (int) ($image['position'] ?? 0),
            ];
        }

        usort($orderingPool, static function (array $a, array $b): int {
            if ($a['order'] === $b['order']) {
                return $a['id'] <=> $b['id'];
            }

            return $a['order'] <=> $b['order'];
        });

        $desiredOrder = [];
        if ($coverCandidate > 0) {
            $desiredOrder[] = $coverCandidate;
        }

        foreach ($orderingPool as $item) {
            $desiredOrder[] = $item['id'];
        }

        $this->vehicleModel->reorderImages($vehicleId, $desiredOrder);
    }


    /**
     * Persiste temporalmente inputs y errores para mostrarlos luego del redirect.
     */
    private function rememberFormState(array $data, array $errors): void
    {
        $_SESSION['vehicle_form_old'] = $data;
        $_SESSION['vehicle_form_errors'] = $errors;
    }

    /**
     * Valida y guarda hasta 5 imágenes, devolviendo métricas para la UI.
     */
    private function processVehicleImagesUpload(int $vehicleId): array
    {
        $result = ['uploaded' => 0, 'skipped' => 0, 'errors' => [], 'notes' => []];

        if ($vehicleId <= 0 || empty($_FILES['vehicle_photos']['name'])) {
            return $result;
        }

        $allowedMime = ['image/jpeg' => 'JPG/JPEG', 'image/png' => 'PNG'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        $maxFilesPerRequest = 5;
        $uploadDir = dirname(__DIR__) . '/Public/Uploads/Vehicles/' . $vehicleId;

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            $result['errors'][] = 'No se pudo preparar la carpeta para guardar las fotos.';
            return $result;
        }

        $files = $_FILES['vehicle_photos'];
        if (!is_array($files['name'])) {
            return $result;
        }

        $validNames = array_values(array_filter($files['name'], static fn($name) => $name !== ''));
        if (count($validNames) > $maxFilesPerRequest) {
            $result['errors'][] = 'Podés subir hasta 5 imágenes por cada envío.';
        }

        $position = $this->vehicleModel->nextImagePosition($vehicleId);

        $processed = 0;
        $total = count($files['name']);

        for ($i = 0; $i < $total; $i++) {
            if ($processed >= $maxFilesPerRequest) {
                $result['errors'][] = 'Solo se procesaron las primeras 5 imágenes enviadas.';
                break;
            }

            $originalName = $files['name'][$i] ?? '';
            if ($originalName === '') {
                continue;
            }

            $errorCode = $files['error'][$i] ?? UPLOAD_ERR_NO_FILE;
            if ($errorCode !== UPLOAD_ERR_OK) {
                $result['errors'][] = sprintf('No se pudo subir "%s". Intentalo nuevamente.', $originalName);
                $result['skipped']++;
                continue;
            }

            $tmpName = $files['tmp_name'][$i] ?? '';
            if (!is_file($tmpName)) {
                $result['errors'][] = sprintf('El archivo "%s" no es válido.', $originalName);
                $result['skipped']++;
                continue;
            }

            $size = (int) ($files['size'][$i] ?? 0);
            $mime = mime_content_type($tmpName) ?: '';

            if ($size <= 0 || $size > $maxSize) {
                $result['errors'][] = sprintf('"%s" supera el límite de 2 MB.', $originalName);
                $result['skipped']++;
                continue;
            }

            if (!array_key_exists($mime, $allowedMime)) {
                $result['errors'][] = sprintf('"%s" debe estar en formato JPG o PNG.', $originalName);
                $result['skipped']++;
                continue;
            }

            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) ?: ($mime === 'image/png' ? 'png' : 'jpg');
            $safeName = uniqid('vehicle_', true) . '.' . $extension;
            $destination = $uploadDir . '/' . $safeName;

            if (!move_uploaded_file($tmpName, $destination)) {
                $result['errors'][] = sprintf('No se pudo guardar "%s" en el servidor.', $originalName);
                $result['skipped']++;
                continue;
            }

            $processed++;
            $position++;
            $relativePath = str_replace(dirname(__DIR__) . '/Public/', '', $destination);

            $this->vehicleModel->insertImage($vehicleId, $originalName, $relativePath, $position);

            $result['uploaded']++;
        }

        if ($result['uploaded'] > 0) {
            $result['notes'][] = sprintf('Se guardaron %d foto%s.', $result['uploaded'], $result['uploaded'] === 1 ? '' : 's');
        }

        if ($result['skipped'] > 0) {
            $result['notes'][] = sprintf('%d archivo%s se descartaron.', $result['skipped'], $result['skipped'] === 1 ? '' : 's');
        }

        return $result;
    }

    /**
     * Helper para borrar archivos físicos cuando ya se quitaron de la BD.
     */
    private function removeImageFromDisk(?string $relativePath): void
    {
        if (empty($relativePath)) {
            return;
        }

        $absolutePath = dirname(__DIR__) . '/Public/' . ltrim($relativePath, '/');
        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    /**
     * Normaliza un arreglo arbitrario convirtiéndolo en IDs positivos únicos.
     */
    private function sanitizeIdArray($values): array
    {
        if (!is_array($values)) {
            return [];
        }

        $ids = array_map('intval', $values);
        $ids = array_filter($ids, static fn($id) => $id > 0);

        return array_values(array_unique($ids));
    }

    /**
     * Convierte un mapa de posiciones enviado por el formulario en enteros.
     */
    private function sanitizePositionsArray($values): array
    {
        if (!is_array($values)) {
            return [];
        }

        $positions = [];

        foreach ($values as $key => $value) {
            $imageId = (int) $key;
            if ($imageId <= 0) {
                continue;
            }

            $positions[$imageId] = (int) $value;
        }

        return $positions;
    }

    /**
     * Permite procesar la versión compacta del estado enviada vía JSON.
     */
    private function parseGalleryStateFromJson(): array
    {
        $raw = trim((string)($_POST['gallery_state'] ?? ''));
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || empty($decoded['items']) || !is_array($decoded['items'])) {
            return [];
        }

        $coverId = 0;
        $positions = [];
        $deleteIds = [];

        foreach ($decoded['items'] as $item) {
            if (!is_array($item)) {
                continue;
            }

            $imageId = (int) ($item['id'] ?? 0);
            if ($imageId <= 0) {
                continue;
            }

            if (!empty($item['delete'])) {
                $deleteIds[] = $imageId;
            }

            if (array_key_exists('order', $item)) {
                $positions[$imageId] = (int) $item['order'];
            }

            if (!empty($item['cover'])) {
                $coverId = $imageId;
            }
        }

        return [
            'positions' => $positions,
            'delete' => array_values(array_unique($deleteIds)),
            'cover' => $coverId,
        ];
    }

    /**
     * Distribuye los mensajes de carga en los diferentes contextos (lista/form).
     */
    private function flashUploadFeedback(array $feedback, array $scopes = []): void
    {
        if (empty($scopes)) {
            return;
        }

        foreach ($scopes as $scope) {
            $hasPayload = ($feedback['uploaded'] ?? 0) > 0
                || ($feedback['skipped'] ?? 0) > 0
                || !empty($feedback['errors'])
                || !empty($feedback['notes']);

            if (!$hasPayload) {
                continue;
            }

            if (!empty($feedback['errors'])) {
                $_SESSION['vehicle_upload_errors'][$scope] = array_merge(
                    $_SESSION['vehicle_upload_errors'][$scope] ?? [],
                    $feedback['errors']
                );
            }

            $_SESSION['vehicle_upload_summary'][$scope] = [
                'uploaded' => (int) ($feedback['uploaded'] ?? 0),
                'skipped' => (int) ($feedback['skipped'] ?? 0),
                'notes' => $feedback['notes'] ?? [],
            ];
        }
    }

    /**
     * Obtiene y limpia los errores de upload asociados a un ámbito dado.
     */
    private function consumeUploadWarnings(string $scope = 'list'): array
    {
        $warnings = $_SESSION['vehicle_upload_errors'][$scope] ?? [];
        unset($_SESSION['vehicle_upload_errors'][$scope]);

        if (empty($_SESSION['vehicle_upload_errors'])) {
            unset($_SESSION['vehicle_upload_errors']);
        }

        return $warnings;
    }

    /**
     * Lee un resumen previo del proceso de carga para mostrarlo en la vista.
     */
    private function consumeUploadSummary(string $scope = 'list'): array
    {
        $summary = $_SESSION['vehicle_upload_summary'][$scope] ?? [];
        unset($_SESSION['vehicle_upload_summary'][$scope]);

        if (empty($_SESSION['vehicle_upload_summary'])) {
            unset($_SESSION['vehicle_upload_summary']);
        }

        return $summary;
    }

    /**
     * Detecta errores típicos de duplicados para devolver feedback amigable.
     */
    private function handleVehicleDbException(\PDOException $exception, array $formData, string $route, array $params = []): void
    {
        $errors = [];
        $errorCode = $exception->errorInfo[1] ?? null;

        if ($errorCode === 1062) {
            $message = $exception->getMessage();

            if (stripos($message, 'internal_code') !== false) {
                $errors['internal_code'] = 'Ese padrón ya está registrado.';
            }

            if (stripos($message, 'license_plate') !== false) {
                $errors['license_plate'] = 'Esa patente ya está registrada.';
            }

            if (empty($errors)) {
                $errors['general'] = 'El registro ya existe.';
            }
        }

        if (empty($errors)) {
            throw $exception;
        }

        $this->rememberFormState($formData, $errors);
        $this->redirectToRoute($route, $params);
    }

    /**
     * Recupera los datos guardados en sesión y los descarta para evitar fugas.
     */
    private function retrieveFormState(): array
    {
        $data = $_SESSION['vehicle_form_old'] ?? [];
        $errors = $_SESSION['vehicle_form_errors'] ?? [];
        unset($_SESSION['vehicle_form_old'], $_SESSION['vehicle_form_errors']);

        return [$data, $errors];
    }

    /**
     * Bloquea el acceso a usuarios no autenticados.
     */
    private function ensureAuthenticated(): void
    {
        if (empty($_SESSION['auth_user_id'])) {
            $this->redirectToRoute('auth/login');
        }
    }

    /**
     * Evita que rutas destructivas se ejecuten por GET.
     */
    private function assertPostRequest(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirectToRoute('vehicles');
        }
    }

    /**
     * Construye una URL interna con query params y fuerza la redirección.
     */
    private function redirectToRoute(string $route, array $params = []): void
    {
        $query = array_merge(['route' => $route], $params);
        $location = 'index.php?' . http_build_query($query);
        header('Location: ' . $location);
        exit;
    }
}

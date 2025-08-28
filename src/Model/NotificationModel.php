<?php

namespace App\Model;

class NotificationModel
{
    public function findAll(): array
    {
        return [
            [
                'id' => 1,
                'title' => 'Intervention Delayed',
                'description' => 'Field Clearing Operation has been delayed by 2 hours',
                'type' => 'Avertissement',
                'type_class' => 'warning',
                'icon' => 'bx-error-alt',
                'related_to' => 'Intervention: Field Clearing Operation',
                'date' => '15/05/2025',
                'read' => false
            ],
            [
                'id' => 2,
                'title' => 'Vehicle Maintenance Required',
                'description' => 'Quad Explorer X450 is due for scheduled maintenance',
                'type' => 'Alerte',
                'type_class' => 'danger',
                'icon' => 'bx-error-circle',
                'related_to' => 'Véhicule: Quad Explorer X450',
                'date' => '15/05/2025',
                'read' => false
            ],
            [
                'id' => 3,
                'title' => 'Intervention Completed',
                'description' => 'Emergency Bridge Repair has been successfully completed',
                'type' => 'Succès',
                'type_class' => 'success',
                'icon' => 'bx-check-circle',
                'related_to' => 'Intervention: Emergency Bridge Repair',
                'date' => '14/05/2025',
                'read' => true
            ],
            [
                'id' => 4,
                'title' => 'New Member Assigned',
                'description' => 'John Doe has been assigned to Équipe Alpha',
                'type' => 'Information',
                'type_class' => 'info',
                'icon' => 'bx-info-circle',
                'related_to' => 'Équipe: Équipe Alpha',
                'date' => '13/05/2025',
                'read' => true
            ]
        ];
    }
} 
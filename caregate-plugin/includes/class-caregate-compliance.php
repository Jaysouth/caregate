<?php
/**
 * Compliance management functionality.
 */

class CareGate_Compliance {

    const REQUIRED_DOCUMENTS = array(
        'DBS_CHECK',
        'RIGHT_TO_WORK',
        'PROFESSIONAL_REGISTRATION',
        'HEALTH_CLEARANCE',
        'MANDATORY_TRAINING',
        'LIABILITY_INSURANCE'
    );

    public static function create_compliance_record($request) {
        global $wpdb;
        
        $params = $request->get_json_params();
        $worker_id = intval($params['workerId'] ?? 0);
        $document_type = sanitize_text_field($params['documentType'] ?? '');
        $document_number = sanitize_text_field($params['documentNumber'] ?? '');
        $issue_date = sanitize_text_field($params['issueDate'] ?? '');
        $expiry_date = sanitize_text_field($params['expiryDate'] ?? '');
        $status = sanitize_text_field($params['status'] ?? 'pending');

        if (!$worker_id || !$document_type || !$status) {
            return new WP_Error('missing_fields', 'Missing required fields', array('status' => 400));
        }

        $table = $wpdb->prefix . 'caregate_compliance';
        $wpdb->insert(
            $table,
            array(
                'worker_id' => $worker_id,
                'document_type' => $document_type,
                'document_number' => $document_number,
                'issue_date' => $issue_date ?: null,
                'expiry_date' => $expiry_date ?: null,
                'status' => $status,
                'verified_at' => $status === 'valid' ? current_time('mysql') : null
            )
        );

        $record = self::get_record_by_id($wpdb->insert_id);

        return new WP_REST_Response(array(
            'message' => 'Compliance record created successfully',
            'record' => $record
        ), 201);
    }

    public static function get_worker_compliance($request) {
        global $wpdb;
        
        $worker_id = $request->get_param('id');
        $table = $wpdb->prefix . 'caregate_compliance';
        
        $records = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE worker_id = %d",
            $worker_id
        ), ARRAY_A);

        $formatted_records = array_map(array(self::class, 'format_record'), $records);

        // Check compliance status
        $document_types = array_column($records, 'document_type');
        $missing_documents = array_diff(self::REQUIRED_DOCUMENTS, $document_types);

        $expired_documents = array();
        $now = current_time('timestamp');
        foreach ($records as $record) {
            if ($record['expiry_date'] && strtotime($record['expiry_date']) < $now) {
                $expired_documents[] = $record['document_type'];
            }
        }

        $is_fully_compliant = empty($missing_documents) && 
                             empty($expired_documents) &&
                             count(array_filter($records, function($r) { return $r['status'] === 'valid'; })) === count(self::REQUIRED_DOCUMENTS);

        $completion_percentage = round((count($document_types) / count(self::REQUIRED_DOCUMENTS)) * 100);

        return new WP_REST_Response(array(
            'records' => $formatted_records,
            'compliance' => array(
                'isFullyCompliant' => $is_fully_compliant,
                'missingDocuments' => array_values($missing_documents),
                'expiredDocuments' => $expired_documents,
                'completionPercentage' => $completion_percentage
            )
        ), 200);
    }

    public static function get_required_documents($request) {
        $documents = array();
        foreach (self::REQUIRED_DOCUMENTS as $doc) {
            $documents[] = array(
                'type' => $doc,
                'name' => ucwords(str_replace('_', ' ', strtolower($doc)))
            );
        }

        return new WP_REST_Response(array('documents' => $documents), 200);
    }

    private static function get_record_by_id($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'caregate_compliance';
        $record = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id), ARRAY_A);
        return $record ? self::format_record($record) : null;
    }

    private static function format_record($record) {
        return array(
            'id' => $record['id'],
            'workerId' => $record['worker_id'],
            'documentType' => $record['document_type'],
            'documentNumber' => $record['document_number'],
            'issueDate' => $record['issue_date'],
            'expiryDate' => $record['expiry_date'],
            'status' => $record['status'],
            'verifiedAt' => $record['verified_at']
        );
    }
}

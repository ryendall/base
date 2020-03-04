<?php
namespace im\model;

class NetworkType extends Base {

    const DB_TABLE = 'm_network';
    const PRIMARY_KEYS = ['network_id'];
    const DB_LISTS = [
        1 => ['true'=>'Yes', 'false'=>'No'],
        2 => ['timestamp'=>'Timestamp', 'reference'=>'Reference'],
        3 => ['error'=>'Error', 'replace'=>'Replace', 'adjust'=>'Adjust', 'new'=>'New'],
        4 => ['no'=>'No', 'yes'=>'Yes'],
        5 => ['true'=>'Yes', 'false'=>'No', 'delete'=>'Delete'],
        6 => ['false'=>'No', 'true'=>'Yes'],
        7 => ['manual'=>'manual', 'auto'=>'auto', 'other'=>'other'],
        8 => ['yes'=>'Yes', 'no'=>'No'],
        9 => ['manual'=>'manual', 'auto'=>'auto', 'unknown'=>'unknown'],
        10 => ['true'=>'Yes', 'false'=>'No', 'unknown'=>'Unknown'],
        11 => ['auto'=>'Auto', 'lookup'=>'Lookup', 'manual'=>'Manual', 'unknown'=>'Unknown'],
        12 => Constants::LIST_ADMIN_USERS
    ];

    const DB_MODEL = [
        'network_id'                         => ["type"=>"key"],
        'network'                            => ["type"=>"txt", "required"=>true],
        'data_method'                        => ["type"=>"txt"],
        'webservice'                         => ["type"=>"txt"],
        'extra_tmp_fields'                   => ["type"=>"txt"],
        'reference_is_numeric'               => ["type"=>"txt", "default"=>"true", "list"=>1],
        'clickref_string_1'                  => ["type"=>"txt"],
        'affiliateid_string_1'               => ["type"=>"txt"],
        'prog_url'                           => ["type"=>"txt"],
        'can_claim'                          => ["type"=>"txt", "default"=>"true", "list"=>1],
        'link_format'                        => ["type"=>"txt"],
        'uses_clickid_as_reference'          => ["type"=>"txt", "default"=>"false", "list"=>6],
        'store_text'                         => ["type"=>"txt", "default"=>"false", "list"=>6],
        'mref_string_1'                      => ["type"=>"txt"],
        'min_payment_status'                 => ["type"=>"num", "default"=>20],
        'short_tmp_table'                    => ["type"=>"txt", "default"=>"false", "list"=>1],
        'guarantee'                          => ["type"=>"txt", "default"=>"false", "list"=>1],
        'prog_user_id'                       => ["type"=>"num", "default"=>827, "list"=>12],
        'oor_user_id'                        => ["type"=>"num", "default"=>57, "list"=>12],
        'query_user_id'                      => ["type"=>"num", "default"=>450, "list"=>12],
        'claims_user_id'                     => ["type"=>"num", "default"=>827, "list"=>12],
        'can_apply_via_api'                  => ["type"=>"txt", "default"=>"false", "list"=>1],
        'claim_status_url'                   => ["type"=>"txt"],
        'report_time_offset'                 => ["type"=>"num"],
        'mref_string_2'                      => ["type"=>"txt"],
        'prog_check_method'                  => ["type"=>"txt", "default"=>"unknown", "list"=>9],
        'alternative_hosts'                  => ["type"=>"txt"],
        'link_provided'                      => ["type"=>"txt", "default"=>"unknown", "list"=>11],
        'can_follow_url2'                    => ["type"=>"txt", "default"=>"unknown", "list"=>10],
        'url2_error_string'                  => ["type"=>"txt"],
        'date_sep'                           => ["type"=>"txt", "default"=>"/"],
        'time_sep'                           => ["type"=>"txt", "default"=>":"],
        'money_sep'                          => ["type"=>"txt", "default"=>"."],
        'time_variance'                      => ["type"=>"num"],
        'status_updates'                     => ["type"=>"txt", "default"=>"true", "list"=>1],
        'match_by'                           => ["type"=>"txt", "default"=>"reference", "list"=>2],
        'award_days'                         => ["type"=>"num", "default"=>60],
        'revenue_changes'                    => ["type"=>"txt", "default"=>"error", "list"=>3],
        'revenue_zeroed_on_rejection'        => ["type"=>"txt", "default"=>"no", "list"=>4],
        'oor_if_no_reference'                => ["type"=>"txt", "default"=>"false", "list"=>5],
        'merchant_ref_required'              => ["type"=>"txt", "default"=>"false", "list"=>6],
        'merchant_ref_prompt'                => ["type"=>"txt"],
        'claim_approved_status'              => ["type"=>"num", "default"=>10],
        'claims_auto_acknowledged'           => ["type"=>"num"],
        'sum_trans_by_reference'             => ["type"=>"txt", "default"=>"false", "list"=>1],
        'claims_added_with_original_clickid' => ["type"=>"txt", "default"=>"false", "list"=>1],
        'upload_method'                      => ["type"=>"txt", "list"=>7],
        'clickref_string_2'                  => ["type"=>"txt"],
        'region_id'                          => ["type"=>"num", "default"=>1],
        'currency_id'                        => ["type"=>"num", "default"=>1],
        'language_id'                        => ["type"=>"num", "default"=>1],
        'affiliateid_string_2'               => ["type"=>"txt"],
        'join_url'                           => ["type"=>"txt"],
        'claims_url'                         => ["type"=>"txt"],
        'show_on_suggest_form'               => ["type"=>"txt", "default"=>"yes", "list"=>8],
        'claim_days'                         => ["type"=>"num", "default"=>7],
        'claim_response_days'                => ["type"=>"num", "default"=>80],
        'link_lookup'                        => ["type"=>"txt"],
        // 'auto_commissions' // superseded by m_aff.auto_commission_feed
    ];
}
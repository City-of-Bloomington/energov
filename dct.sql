create table contact (
    contact_id              varchar(100) not null primary key,
    company_name            varchar(200),
    first_name              varchar(100),
    middle_name             varchar(100),
    last_name               varchar(100),
    is_company              bit          not null,
    is_individual           bit          not null,
    email                   varchar(500),
    website                 varchar(200),
    business_phone          varchar(100),
    home_phone              varchar(100),
    mobile_phone            varchar(100),
    other_phone             varchar(100),
    fax                     varchar(100),
    title                   varchar(100),
    last_update_date        datetime,
    last_update_user        varchar(100),
    isactive                bit          not null,
    legacy_data_source_name varchar(200) not null
);

create table contact_address (
    contact_id        varchar(100) not null,
    main_address      bit,
    address_type      varchar(100),
    street_number     varchar(400),
    pre_direction     varchar(60 ),
    street_name       varchar(400),
    street_type       varchar(100),
    post_direction    varchar(60 ),
    unit_suite_number varchar(40 ),
    address_line_3    varchar(400),
    po_box            varchar(100),
    city              varchar(100),
    state_code        varchar(100),
    province          varchar(100),
    zip               varchar(100),
    county_code       varchar(100),
    country_code      varchar(100),
    country_type      varchar(100) not null,
    last_update_date  datetime,
    last_update_user  varchar(100),
    foreign key (contact_id) references contact(contact_id)
);

create table contact_note (
    contact_id varchar(100) not null,
    note_text  varchar(500) not null,
    note_title varchar(100),
    note_user  varchar(100),
    note_date  date,
    foreign key (contact_id) references contact(contact_id)
);

create table permit (
    permit_number           varchar(100) not null primary key,
    permit_type             varchar(200),
    permit_sub_type         varchar(200),
    permit_status           varchar(200),
    district                varchar(200),
    apply_date              date,
    permit_description      varchar(200),
    issue_date              date,
    expire_date             date,
    final_date              date,
    last_update_date        datetime,
    last_update_user        varchar(100),
    last_inspection_date    date,
    valuation               money,
    square_footage          decimal (9),
    project_number          varchar(100),
    assigned_to             varchar(200),
    legacy_data_source_name varchar(200) not null
);

create table permit_contact (
    permit_number   varchar(100) not null,
    contact_id      varchar(100) not null,
    contact_type    varchar(32),
    primary_billing_contact bit,
    foreign key (permit_number) references permit (permit_number),
    foreign key (contact_id   ) references contact(contact_id)
);

create table permit_address (
    permit_number     varchar(100) not null,
    main_address      bit,
    address_type      varchar(100),
    street_number     varchar(400),
    pre_direction     varchar(60 ),
    street_name       varchar(400),
    street_type       varchar(100),
    post_direction    varchar(60 ),
    unit_suite_number varchar(40 ),
    address_line_3    varchar(400),
    po_box            varchar(100),
    city              varchar(100),
    state_code        varchar(100),
    province          varchar(100),
    zip               varchar(100),
    county_code       varchar(100),
    country_code      varchar(100),
    country_type      varchar(100) not null,
    last_update_date  datetime,
    last_update_user  varchar(100),
    foreign key (permit_number) references permit(permit_number)
);

create table permit_note (
    permit_number varchar(100) not null,
    note_text     varchar(510) not null,
    note_title    varchar(100),
    note_user     varchar(100),
    note_date     datetime,
    foreign key (permit_number) references permit(permit_number)
);

create table permit_activity (
    activity_number  int          not null primary key identity,
    permit_number    varchar(100) not null,
    activity_type    varchar(100) not null,
    activity_comment varchar(100),
    activity_user    varchar(100),
    activity_date    date,
    foreign key (permit_number) references permit(permit_number)
);

create table inspection (
    inspection_number	    varchar(100) not null primary key,
    inspection_type	        varchar(100),
    inspection_status	    varchar(100),
    create_date	            date,
    requested_for_date	    date,
    scheduled_for_date	    date,
    attempt_number	        int,
    completed	            bit,
    last_update_date	    datetime,
    last_update_user	    varchar(100),
    inspector	            varchar(100),
    inspected_date_start    date,
    inspected_date_end      date,
    comment                 varchar(1024),
    inspection_case_number	varchar(100),
    legacy_data_source_name varchar(200) not null
);

create table permit_inspection (
    permit_number     varchar(100) not null,
    inspection_number varchar(100) not null,
    foreign key (permit_number    ) references permit(permit_number),
    foreign key (inspection_number) references inspection(inspection_number)
);

create table permit_fee (
    permit_fee_id           varchar(100) not null primary key,
    permit_number           varchar(100) not null,
    fee_type                varchar(100),
    fee_amount              money        not null,
    fee_date                date,
    created_by_user         varchar(100),
    input_value             decimal,
    fee_note                varchar(100),
    legacy_data_source_name varchar(200) not null
    foreign key (permit_number) references permit(permit_number)
);

create table payment (
    payment_id      int          not null primary key identity,
    receipt_number  varchar(100) not null,
    payment_method  varchar(100),
    check_number    varchar(100),
    payment_amount  money        not null,
    payment_date    date         not null,
    created_by_user varchar(100),
    payment_note    varchar(100)
);

create table permit_payment_detail (
    permit_fee_id varchar(100) not null,
    payment_id    int          not null,
    paid_amount   money        not null,
    foreign key (permit_fee_id) references permit_fee(permit_fee_id),
    foreign key (   payment_id) references    payment(   payment_id)
);

create table attachment_document (
    doc_id             int          not null primary key identity,
    parent_case_number varchar(100) not null,
    parent_case_table  varchar(100) not null,
    file_path          varchar(400),
    file_name          varchar(100) not null,
    doc_comment        varchar(100),
    doc_date           datetime,
    attached_by        varchar(100),
    attachment_group   varchar(100),
    document_data      varbinary(max),
    tcmdocid           varchar(510)
);

create table bond (
    bond_id               varchar(100) not null primary key,
    bond_number           varchar(100) not null,
    bond_type             varchar(100) not null,
    bond_status           varchar(100) not null,
    issue_date            date,
    expire_date           date,
    release_date          date,
    amount                money        not null,
    obligee_contact_id    varchar(100),
    principal_contact_id  varchar(100),
    surety_contact_id     varchar(100),
    global_entity_account_number varchar(100),
    foreign key (  obligee_contact_id) references contact(contact_id),
    foreign key (principal_contact_id) references contact(contact_id),
    foreign key (   surety_contact_id) references contact(contact_id)
);

create table bond_note (
    bond_id    varchar(100) not null,
    note_text  varchar(500) not null,
    note_title varchar(100),
    note_user  varchar(100),
    note_date  date,
    foreign key (bond_id) references bond(bond_id)
);

create table code_case (
    case_number          varchar(100) not null primary key,
    case_type            varchar(100) not null,
    case_status          varchar(100),
    district             varchar(100),
    open_date            date         not null,
    case_description     varchar(1024),
    closed_date          date,
    assigned_to_user     varchar(100),
    created_by_user      varchar(100),
    last_update_date     date,
    last_update_user     varchar(100),
    project_number       varchar(100),
    parent_permit_number varchar(100),
    parent_plan_number   varchar(100),
    foreign key (parent_permit_number) references permit(permit_number)
);

create table code_case_contact (
    case_number     varchar(100) not null,
    contact_id      varchar(100) not null,
    contact_type    varchar(32),
    primary_billing_contact bit,
    foreign key (case_number) references code_case(case_number),
    foreign key (contact_id ) references contact(contact_id)
);

create table code_case_violation (
    violation_number       varchar(100) not null primary key,
    case_number            varchar(100) not null,
    violation_code         varchar(100),
    violation_status       varchar(100),
    violation_priority     varchar(100),
    violation_note         varchar(1024),
    corrective_action_memo varchar(1024),
    citation_date          date   not null,
    compliance_date        date,
    resolved_date          date,
    foreign key (case_number) references code_case(case_number)
);

create table code_case_address (
    case_number       varchar(100) not null,
    main_address      bit,
    address_type      varchar(100),
    street_number     varchar(400),
    pre_direction     varchar(60 ),
    street_name       varchar(400),
    street_type       varchar(100),
    post_direction    varchar(60 ),
    unit_suite_number varchar(40 ),
    address_line_3    varchar(400),
    po_box            varchar(100),
    city              varchar(100),
    state_code        varchar(100),
    province          varchar(100),
    zip               varchar(100),
    county_code       varchar(100),
    country_code      varchar(100),
    country_type      varchar(100) not null,
    last_update_date  datetime,
    last_update_user  varchar(100),
    foreign key (case_number) references code_case(case_number)
);

create table contact (
    contact_id              int          not null primary key identity,
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
    mobile_phon             varchar(100),
    other_phone             varchar(100),
    fax                     varchar(100),
    title                   varchar(100),
    last_update_date        datetime,
    last_update_user        varchar(100),
    legacy_data_source_name varchar(200),
    isactive                bit          not null
);

create table contact_address (
    contact_id        int          not null,
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
    contact_id int          not null,
    note_text  varchar(200) not null,
    note_title varchar(100),
    note_user  varchar(100),
    note_date  date,
    foreign key (contact_id) references contact(contact_id)
);

create table permit (
    permit_number           int          not null primary key identity,
    permit_type             varchar(200),
    permit_sub_type         varchar(200),
    permit_status           varchar(200),
    district                varchar(200),
    apply_date              date         not null,
    permit_description      varchar(200),
    issue_date              date,
    expire_date             date,
    final_date              date,
    last_update_date        datetime,
    last_update_user        varchar(100),
    last_inspection_date    date,
    valuation               money,
    square_footage          decimal (9),
    legacy_data_source_name varchar(200),
    project_number          varchar(100),
    assigned_to             varchar(200)
);

create table permit_contact (
    permit_number   int not null,
    contact_id      int not null,
    contact_type    varchar(32),
    primary_billing_contact bit,
    foreign key (permit_number) references permit (permit_number),
    foreign key (contact_id   ) references contact(contact_id)
);

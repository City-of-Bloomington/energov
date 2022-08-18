select      i.iminspectionid,
            i.actualstartdate,
            i.actualenddate,
       permit.PMPERMITID,
       permit.PERMITNUMBER,
       permit.ISSUEDATE,
       permit.EXPIREDATE,
permitaddress.ADDRESSLINE1 as permit_address,
 owneraddress.name         as owner_name,
 owneraddress.ADDRESSLINE1 as owner_address,
 owneraddress.CITY         as owner_city,
 owneraddress.STATE        as owner_state,
 owneraddress.POSTALCODE   as owner_zip,
 agentaddress.name         as agent_name,
 agentaddress.ADDRESSLINE1 as agent_address,
 agentaddress.CITY         as agent_city,
 agentaddress.STATE        as agent_state,
 agentaddress.POSTALCODE   as agent_zip
from dbo.iminspection                    i
join dbo.iminspectioncase                ic on i.iminspectioncaseid=ic.iminspectioncaseid
join dbo.pmpermit                    permit on permit.pmpermitid=ic.linkid
join dbo.CUSTOMSAVERPERMITMANAGEMENT fields on permit.PMPERMITID=fields.ID
join dbo.PMPERMITADDRESS                pla on permit.PMPERMITID=pla.PMPERMITID
join dbo.MAILINGADDRESS       permitaddress on pla.MAILINGADDRESSID=permitaddress.MAILINGADDRESSID
left join (
    select oc.PMPERMITID,
           oa.ADDRESSLINE1,
           oa.CITY,
           oa.STATE,
           oa.POSTALCODE,
           case oe.iscompany
            when 1 then oe.globalentityname
            else concat_ws(oe.firstname, oe.middlename, oe.lastname)
           end as name
    from dbo.PMPERMITCONTACT            oc
    join dbo.LANDMANAGEMENTCONTACTTYPE  ot on oc.LANDMANAGEMENTCONTACTTYPEID=ot.LANDMANAGEMENTCONTACTTYPEID
    join dbo.GLOBALENTITY               oe on oc.GLOBALENTITYID=oe.GLOBALENTITYID
    join dbo.GLOBALENTITYMAILINGADDRESS om on oe.GLOBALENTITYID=om.GLOBALENTITYID
    join dbo.MAILINGADDRESS             oa on om.MAILINGADDRESSID=oa.MAILINGADDRESSID
    where ot.NAME='Owner'
) owneraddress on permit.PMPERMITID=owneraddress.PMPERMITID
left join (
    select ac.PMPERMITID,
           aa.ADDRESSLINE1,
           aa.CITY,
           aa.STATE,
           aa.POSTALCODE,
           case ae.iscompany
            when 1 then ae.globalentityname
            else concat_ws(ae.firstname, ae.middlename, ae.lastname)
           end as name
    from dbo.PMPERMITCONTACT            ac
    join dbo.LANDMANAGEMENTCONTACTTYPE  at on ac.LANDMANAGEMENTCONTACTTYPEID=at.LANDMANAGEMENTCONTACTTYPEID
    join dbo.GLOBALENTITY               ae on ac.GLOBALENTITYID=ae.GLOBALENTITYID
    join dbo.GLOBALENTITYMAILINGADDRESS am on ae.GLOBALENTITYID=am.GLOBALENTITYID
    join dbo.MAILINGADDRESS             aa on am.MAILINGADDRESSID=aa.MAILINGADDRESSID
    where at.NAME='Agent'
) agentaddress on permit.PMPERMITID=agentaddress.PMPERMITID
where i.iminspectionid='c9486133-c04d-4cc9-aef9-2b52dd0b1c50';

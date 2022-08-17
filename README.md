Post Migration
--------------
After running the migration, we need to manually add some information that could not be migrated programmatically.

## Rental Payments for unpaid bills
We migrated all the unpaid bills, but a few of them might have some portion already paid.  We'll need to manually enter the payments for the not-fully-paid bills.  Last time I checked, there was only one bill, but that could change over time.

```sql
select b.bid, b.id, count(*)
from rental.registr        r
join rental.reg_bills b on b.bid=(select max(bid) from rental.reg_bills where id=r.id)
join rental.REG_PAID  p on b.bid=p.bid
where b.status!='Paid'
  and r.inactive is null
group by b.bid, b.id;
```

## Rental Properties with multiple addresses
There are around 200 rental properties with multiple addresses.  Most of these can be identified by having multiple buildings; since each building usually gets it's own address.

The multiple addresses will need to be added to manually to the permit.

```sql
select r.id, count(*)
from rental.registr r
join rental.rental_structures s on r.id=s.rid
where r.inactive is null
group by r.id having count(*)>1;
```

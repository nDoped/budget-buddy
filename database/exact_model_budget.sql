select count(*) from transactions;
select * from transactions;
select * from transaction_images;
describe transaction_images;
describe transaction;
update
show tables;
describe users;
delete from transactions;
drop table transaction_images;
select * from category_transaction;
select * from categories;
delete from categories;
select * from accounts;
describe categories;
describe accounts;
select * from transactions;
describe category_types;
describe account_types;
describe users;

select * from transactions;
select count(*) from transactions;
select count(*) from categories;
select count(*) from category_transaction;


select * from categories where id = 47\G
show tables;
select * from sessions;
select * from personal_access_tokens;
select * from users\G;
delete from sessions;

describe accounts;
describe transactions;
describe categories;
describe accounts;
describe account_types;
describe category_types;
describe account_types;
select * from category_transaction;

select * from transactions;
select * from category_transaction ct
    inner join transactions t on t.id = ct.transaction_id
    where ct.category_id in (47)\G
;
select * from categories;
select * from accounts;
select * from users;

select * from transactions where id = 124;

select t.id as 'transId',
    t.amount as 'amount',
    c.id as 'cat id',
    ct.percentage,
    c.name
    from transactions t
    inner join category_transaction ct on ct.transaction_id = t.id
    inner join categories c on ct.category_id = c.id
    where t.id in (135, 136)
    ;

update categories set hex_color = '#332513' where name = 'Coffee';

describe accounts
select * from transactions as t
    inner join accounts a on t.account_id = a.id
    inner join users u on u.id = a.user_id
    where a.user_id = (
        select id from users where name = 'Not Sure'
    );



delete t from transactions as t
    inner join accounts a on t.account_id = a.id
    inner join users u on u.id = a.user_id
    where a.user_id = (
        select id from users where name = 'Not Sure'
    );

delete from category_transaction;
delete from categories;
delete from transactions;
delete from accounts;
delete from users;






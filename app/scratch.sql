select count(*) from transactions;
select count(*) from categories;
select count(*) from category_transaction;

select * from categories where id = 47\G

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






# Ad Hoc SQL Module for SilverStripe 3

## Description
Allows user to construct reports using sql select statements to run through model admin.
This module takes sql inputs in the same way you would construct a normal sql query in silverstripe using SQLSelect.
ie you must correctly escape and capitalise.

## Example usage
select: *
from: "Member"
Where: Email = 'admin'
Limit: 1




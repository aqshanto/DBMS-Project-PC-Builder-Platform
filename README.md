# DBMS-Project-E-commerce-Platform
<pre>
erDiagram
    USERS {
        string user_id PK
        string name
        string email
        string password_hash
        string phone
        string address
        string role
    }

    AUTHORS {
        string author_id PK
        string name
        string biography
    }

    PUBLISHERS {
        string publisher_id PK
        string name
    }

    CATEGORIES {
        string category_id PK
        string category_name
    }

    BOOKS {
        string book_id PK
        string title
        string isbn
        float price
        int quantity
        string author_id FK
        string publisher_id FK
        string category_id FK
    }

    ORDERS {
        string order_id PK
        date order_date
        float total_amount
        string status
        string user_id FK
    }

    ORDER_DETAILS {
        string order_details_id PK
        int quantity
        float unit_price
        string order_id FK
        string book_id FK
    }

    BOOK_PRICE_LOG {
        string log_id PK
        float old_price
        float new_price
        date change_date
        string book_id FK
    }

    %% Relationships and Cardinality
    USERS ||--o{ ORDERS : "places"
    AUTHORS ||--o{ BOOKS : "writes"
    PUBLISHERS ||--o{ BOOKS : "publishes"
    CATEGORIES ||--o{ BOOKS : "have"
    ORDERS ||--|{ ORDER_DETAILS : "contains"
    BOOKS ||--o{ ORDER_DETAILS : "included_in"
    BOOKS ||--o{ BOOK_PRICE_LOG : "has"

</pre>

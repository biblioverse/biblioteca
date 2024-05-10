Any book in the `public/books` folder will be added to the database when you run the `books:scan` command. If a book already exists, it will be skipped.

Adding books to the `public/books/consume` will list them in the `/books/new/consume/files` page. 
This is useful when you want to add books to the library but don't want to add them to the database yet or to mount a folder where books might arrive later (Maybe like a download folder 🫢).

When books are added to the database, their "verified" status is set to false and a link to all unverified books is available in the menu.

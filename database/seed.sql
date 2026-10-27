SET NAMES utf8mb4;

INSERT INTO categories (CategoryName, Description)
VALUES
    ('Điện thoại', 'Các sản phẩm điện thoại di động'),
    ('Máy tính', 'Máy tính xách tay và máy tính để bàn'),
    ('Phụ kiện', 'Phụ kiện dành cho thiết bị công nghệ');

INSERT INTO suppliers
    (SupplierName, ContactName, Address, City, PostalCode, Country, Phone)
VALUES
    ('Công ty Công nghệ ABC', 'Nguyễn Văn An',
     '01 Nguyễn Huệ', 'TP. Hồ Chí Minh', '700000', 'Việt Nam', '0901234567'),
    ('Công ty Thiết bị XYZ', 'Trần Minh Bình',
     '25 Lê Lợi', 'Đà Nẵng', '550000', 'Việt Nam', '0912345678');

INSERT INTO customers
    (CustomerName, ContactName, Address, City, PostalCode, Country)
VALUES
    ('Nguyễn Minh Anh', 'Nguyễn Minh Anh',
     '12 Nguyễn Trãi', 'TP. Hồ Chí Minh', '700000', 'Việt Nam'),
    ('Trần Thanh Hà', 'Trần Thanh Hà',
     '35 Hùng Vương', 'Cần Thơ', '900000', 'Việt Nam'),
    ('Lê Quốc Bảo', 'Lê Quốc Bảo',
     '18 Lê Lợi', 'Trà Vinh', '870000', 'Việt Nam');

INSERT INTO employees
    (LastName, FirstName, BirthDate, Photo, Notes)
VALUES
    ('Nguyễn', 'Hoàng Nam', '1995-05-15', NULL, 'Nhân viên bán hàng'),
    ('Trần', 'Thúy An', '1997-09-20', NULL, 'Nhân viên chăm sóc khách hàng');

INSERT INTO shippers (ShipperName, Phone)
VALUES
    ('Giao hàng nhanh', '19001001'),
    ('Giao hàng tiết kiệm', '19001002');

INSERT INTO products
    (ProductCode, ProductName, Description, Unit, Price,
     StockQuantity, IsActive, SupplierID, CategoryID)
VALUES
    ('SP001', 'Điện thoại Smartphone A',
     'Điện thoại thông minh màn hình lớn, phù hợp nhu cầu sử dụng hằng ngày.',
     'Chiếc', 8500000.00, 20, TRUE, 1, 1),
    ('SP002', 'Laptop B',
     'Máy tính xách tay phục vụ học tập và làm việc.',
     'Chiếc', 18500000.00, 10, TRUE, 2, 2),
    ('SP003', 'Chuột không dây C',
     'Chuột không dây nhỏ gọn, kết nối ổn định.',
     'Chiếc', 450000.00, 50, TRUE, 2, 3),
    ('SP004', 'Tai nghe Bluetooth H1',
     'Tai nghe không dây âm thanh rõ nét, thời lượng pin ấn tượng.',
     'Chiếc', 1290000.00, 35, TRUE, 1, 3),
    ('SP005', 'Máy tính bảng iPad Air',
     'Máy tính bảng mỏng nhẹ, hỗ trợ công việc và giải trí hiệu quả.',
     'Chiếc', 18900000.00, 8, TRUE, 2, 2),
    ('SP006', 'Bàn phím cơ F1',
     'Bàn phím cơ có đèn RGB, thiết kế tiện dụng cho người dùng.',
     'Chiếc', 950000.00, 25, TRUE, 2, 3),
    ('SP007', 'Điện thoại Nokia 2720',
     'Điện thoại basic thiết kế bền bỉ, dễ sử dụng, phù hợp nhu cầu cơ bản.',
     'Chiếc', 2200000.00, 18, TRUE, 1, 1),
    ('SP008', 'Tai nghe chụp đầu Studio',
     'Tai nghe chụp đầu chống ồn, âm thanh sống động cho trải nghiệm nghe nhạc.',
     'Chiếc', 1450000.00, 12, TRUE, 1, 3),
    ('SP009', 'Drawing Tablet X',
     'Bảng vẽ kỹ thuật số hỗ trợ thiết kế đồ họa và chỉnh sửa ảnh.',
     'Chiếc', 3990000.00, 9, TRUE, 2, 2);

INSERT INTO product_images
    (ProductID, ImageFile, AltText, IsPrimary, SortOrder)
VALUES
    (1, 'phone-a-1.webp', 'Điện thoại Smartphone A - ảnh chính', TRUE, 1),
    (1, 'phone-a-2.jpg', 'Điện thoại Smartphone A - mặt sau', FALSE, 2),
    (2, 'laptop-b-1.jpeg', 'Laptop B - ảnh chính', TRUE, 1),
    (3, 'mouse-c-1.webp', 'Chuột không dây C - ảnh chính', TRUE, 1),
    (4, 'earsbud.jfif', 'Tai nghe Bluetooth H1 - ảnh chính', TRUE, 1),
    (4, 'headphone.jpg', 'Tai nghe Bluetooth H1 - góc nhìn khác', FALSE, 2),
    (5, 'Ipad.png', 'Máy tính bảng iPad Air - ảnh chính', TRUE, 1),
    (6, 'mouse-c-1.webp', 'Bàn phím cơ F1 - ảnh chính', TRUE, 1),
    (7, 'nokia.jpg', 'Điện thoại Nokia 2720 - ảnh chính', TRUE, 1),
    (8, 'headphone2.webp', 'Tai nghe chụp đầu Studio - ảnh chính', TRUE, 1),
    (9, 'drawingtablet.jpg', 'Drawing Tablet X - ảnh chính', TRUE, 1),
    (8, 'headphone.jpg', 'Tai nghe chụp đầu Studio - ảnh phụ', FALSE, 2);

INSERT INTO orders (OrderDate, CustomerID, EmployeeID, ShipperID)
VALUES
    ('2026-08-10', 1, 1, 1),
    ('2026-08-11', 2, 2, 2),
    ('2026-08-12', 3, 1, 1);

INSERT INTO orderdetail (Quantity, UnitPrice, OrderID, ProductID)
VALUES
    (1, 8500000.00, 1, 1),
    (2, 450000.00, 1, 3),
    (1, 18500000.00, 2, 2),
    (2, 450000.00, 3, 3);
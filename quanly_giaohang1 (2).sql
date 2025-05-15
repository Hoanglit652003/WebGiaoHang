-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th5 10, 2025 lúc 10:58 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `quanly_giaohang1`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `buucuc`
--

CREATE TABLE `buucuc` (
  `id_buuCuc` int(11) NOT NULL,
  `tenBuuCuc` varchar(50) NOT NULL,
  `diaChi` varchar(100) NOT NULL,
  `tinhThanh` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `diachi`
--

CREATE TABLE `diachi` (
  `id_diaChi` int(11) NOT NULL,
  `diaChiNguoiNhan` text NOT NULL,
  `tinh_tp` varchar(100) NOT NULL,
  `quan_huyen` varchar(100) NOT NULL,
  `phuong_xa` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `diachi`
--

INSERT INTO `diachi` (`id_diaChi`, `diaChiNguoiNhan`, `tinh_tp`, `quan_huyen`, `phuong_xa`) VALUES
(19, '123', 'Thành phố Hồ Chí Minh', 'Quận Bình Tân', 'Phường Bình Trị Đông B'),
(20, '192/1 Phan Văn Trị', 'Thành phố Hồ Chí Minh', 'Quận Bình Thạnh', 'Phường 13');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `doanhthu`
--

CREATE TABLE `doanhthu` (
  `id_doanhThu` int(11) NOT NULL,
  `maVanDon` varchar(40) NOT NULL,
  `tongTien` float NOT NULL,
  `ngayTinh` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `donhang`
--

CREATE TABLE `donhang` (
  `maVanDon` varchar(40) NOT NULL,
  `id_khachHang` int(11) NOT NULL,
  `id_nhanVien` int(11) DEFAULT NULL,
  `id_trangThai` int(11) NOT NULL,
  `id_sanPham` int(11) DEFAULT NULL,
  `id_nguoiNhan` int(11) NOT NULL,
  `id_nguoiGui` int(11) NOT NULL,
  `id_thanhToan` int(11) DEFAULT NULL,
  `ngayTaoDon` datetime NOT NULL,
  `ghiChu` text DEFAULT NULL,
  `ngayGiao` datetime DEFAULT NULL,
  `id_phi` int(11) DEFAULT NULL,
  `hinhThucGui` varchar(50) NOT NULL,
  `KL_DH` decimal(10,2) NOT NULL,
  `rong` decimal(10,2) DEFAULT NULL,
  `dai` int(11) DEFAULT NULL,
  `cao` decimal(10,2) DEFAULT NULL,
  `giaTriHang` decimal(15,2) DEFAULT NULL,
  `COD` decimal(15,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `donhang`
--

INSERT INTO `donhang` (`maVanDon`, `id_khachHang`, `id_nhanVien`, `id_trangThai`, `id_sanPham`, `id_nguoiNhan`, `id_nguoiGui`, `id_thanhToan`, `ngayTaoDon`, `ghiChu`, `ngayGiao`, `id_phi`, `hinhThucGui`, `KL_DH`, `rong`, `dai`, `cao`, `giaTriHang`, `COD`) VALUES
('DGH20250510061257', 10, 2, 3, NULL, 8, 19, NULL, '2025-05-10 06:12:57', 'cho xem hàng ', NULL, 7, 'Gửi tại bưu cục', 123.00, 12.00, 12, 12.00, 300000.00, 1000000.00),
('DGH20250510061807', 10, 2, 4, NULL, 9, 20, NULL, '2025-05-10 06:18:07', 'Giao nhanh', NULL, 8, 'Lấy hàng tận nơi', 1.00, 1.00, 1, 1.00, 120000.00, 30000.00);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `hoadon`
--

CREATE TABLE `hoadon` (
  `id_hoaDon` int(11) NOT NULL,
  `maVanDon` varchar(40) NOT NULL,
  `ngayTao` datetime NOT NULL,
  `soTien` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `khachhang`
--

CREATE TABLE `khachhang` (
  `id_khachHang` int(11) NOT NULL,
  `tenKhachHang` varchar(100) NOT NULL,
  `soDienThoai` varchar(15) NOT NULL,
  `diaChi` text NOT NULL,
  `email` varchar(30) NOT NULL,
  `password` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `khachhang`
--

INSERT INTO `khachhang` (`id_khachHang`, `tenKhachHang`, `soDienThoai`, `diaChi`, `email`, `password`) VALUES
(10, 'Phúc Hưng', '0376963735', '592/16 Hồ Học Lãm', 'fisph01@gmail.com', '$2y$10$h051VIq9vIRp49P/SgqEoOuPxmr/P6vwPvR9wZNeL2nIj7AodMe4y');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `khohang`
--

CREATE TABLE `khohang` (
  `id_kho` int(11) NOT NULL,
  `tenKho` varchar(100) NOT NULL,
  `diaChi` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lichlamviec`
--

CREATE TABLE `lichlamviec` (
  `id_lichLamViec` int(11) NOT NULL,
  `id_NhanVien` int(11) NOT NULL,
  `ngayLamViec` date NOT NULL,
  `thoiGianBatDau` time NOT NULL,
  `thoiGianKetThuc` time NOT NULL,
  `ghiChu` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `lichlamviec`
--

INSERT INTO `lichlamviec` (`id_lichLamViec`, `id_NhanVien`, `ngayLamViec`, `thoiGianBatDau`, `thoiGianKetThuc`, `ghiChu`) VALUES
(1, 1, '2025-05-10', '06:00:00', '17:00:00', 'Full'),
(2, 2, '2025-05-10', '11:53:00', '23:53:00', 'full ca'),
(3, 2, '2025-05-09', '06:00:00', '17:00:00', '8 tiếng á');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lichsu_trangthai`
--

CREATE TABLE `lichsu_trangthai` (
  `id_lichSuTrangThai` int(11) NOT NULL,
  `maVanDon` varchar(40) NOT NULL,
  `id_TrangThai` int(11) NOT NULL,
  `mocThoiGian` datetime NOT NULL,
  `diaDiem` varchar(255) DEFAULT NULL,
  `HIMnotes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `lichsu_trangthai`
--

INSERT INTO `lichsu_trangthai` (`id_lichSuTrangThai`, `maVanDon`, `id_TrangThai`, `mocThoiGian`, `diaDiem`, `HIMnotes`) VALUES
(5, 'DGH20250510061257', 1, '2025-05-10 06:12:57', 'Thành phố Hồ Chí Minh', 'Tạo đơn thành công'),
(6, 'DGH20250510061807', 1, '2025-05-10 06:18:07', 'Thành phố Hồ Chí Minh', 'Tạo đơn thành công'),
(7, 'DGH20250510061807', 3, '2025-05-10 14:42:07', 'Thành phố Hồ Chí Minh', 'Cập nhật trạng thái: Đang giao'),
(8, 'DGH20250510061807', 3, '2025-05-10 14:43:13', 'Thành phố Hồ Chí Minh', 'Cập nhật trạng thái: Đang giao'),
(9, 'DGH20250510061807', 3, '2025-05-10 14:44:18', 'Thành phố Hồ Chí Minh', 'Cập nhật trạng thái: Đang giao'),
(10, 'DGH20250510061807', 4, '2025-05-10 14:45:54', 'Thành phố Hồ Chí Minh', 'Cập nhật trạng thái: Đã giao'),
(11, 'DGH20250510061257', 3, '2025-05-10 14:53:11', 'Thành phố Hồ Chí Minh', 'Cập nhật trạng thái: Đang giao'),
(12, 'DGH20250510061257', 3, '2025-05-10 14:59:59', 'Thành phố Hồ Chí Minh', 'Cập nhật trạng thái: Đang giao');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lienhe`
--

CREATE TABLE `lienhe` (
  `id_lienHe` int(11) NOT NULL,
  `ten` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `chuDe` varchar(200) NOT NULL,
  `tinNhan` text NOT NULL,
  `ngayGui` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nguoigui`
--

CREATE TABLE `nguoigui` (
  `id_nguoiGui` int(11) NOT NULL,
  `tenNguoiGui` varchar(100) NOT NULL,
  `diaChiNguoiGui` text NOT NULL,
  `sdtNguoiGui` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `nguoigui`
--

INSERT INTO `nguoigui` (`id_nguoiGui`, `tenNguoiGui`, `diaChiNguoiGui`, `sdtNguoiGui`) VALUES
(19, 'Phan Công Phúc Hưng', '123 đồng nai', '0376963735'),
(20, 'Nguyễn Đình Hoàng', '198/1 Phan Văn Trị', '0981557780');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nguoinhan`
--

CREATE TABLE `nguoinhan` (
  `id_nguoiNhan` int(11) NOT NULL,
  `tenNguoiNhan` varchar(100) NOT NULL,
  `soDienThoai` varchar(15) NOT NULL,
  `id_diaChi` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `nguoinhan`
--

INSERT INTO `nguoinhan` (`id_nguoiNhan`, `tenNguoiNhan`, `soDienThoai`, `id_diaChi`) VALUES
(8, 'Phúc Hưng', '0352237434', 19),
(9, 'Nguyễn Văn A', '0981557782', 20);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nhanvien`
--

CREATE TABLE `nhanvien` (
  `id_nhanVien` int(11) NOT NULL,
  `tenNhanVien` varchar(100) NOT NULL,
  `soDienThoai` varchar(15) NOT NULL,
  `diaChi` text NOT NULL,
  `viTri` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(100) NOT NULL,
  `phanQuyen` int(11) DEFAULT 0,
  `viTriLat` decimal(9,6) DEFAULT NULL,
  `viTriLng` decimal(9,6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `nhanvien`
--

INSERT INTO `nhanvien` (`id_nhanVien`, `tenNhanVien`, `soDienThoai`, `diaChi`, `viTri`, `email`, `password`, `phanQuyen`, `viTriLat`, `viTriLng`) VALUES
(1, 'Nguyễn Đình Hoàng', '0981557780', '198/1 Phan Văn Trị', 'Quản lý', 'hoanglit652003@gmail.com', 'hoanglit652003', 1, 10.821520, 106.686680),
(2, 'Nguyễn Đình Huy', '0981557780', '198/1 Phan Văn Trị', 'Giao hàng', 'hoanglit65@gmail.com', 'hoanglit65', 2, 10.821520, 106.686680),
(4, 'Nguyễn Thị Ánh Vi', '0981557780', 'aaaaa', 'Giao hàng', 'anhvi652003@gmail.com', 'anhvi652003', 2, 10.821520, 106.688990);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phi`
--

CREATE TABLE `phi` (
  `id_phi` int(11) NOT NULL,
  `maVanDon` varchar(20) DEFAULT NULL,
  `phiDichVu` decimal(15,2) NOT NULL,
  `phiKhaiGia` decimal(15,2) DEFAULT 0.00,
  `phiCOD` decimal(15,2) DEFAULT 0.00,
  `tongPhi` decimal(15,2) NOT NULL,
  `benTraPhi` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `phi`
--

INSERT INTO `phi` (`id_phi`, `maVanDon`, `phiDichVu`, `phiKhaiGia`, `phiCOD`, `tongPhi`, `benTraPhi`) VALUES
(7, 'DGH20250510061257', 24000.00, 5000.00, 1000000.00, 1029000.00, 'Người gửi trả phí'),
(8, 'DGH20250510061807', 24000.00, 0.00, 30000.00, 54000.00, 'Người gửi trả phí');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `sanpham`
--

CREATE TABLE `sanpham` (
  `id_sanPham` int(11) NOT NULL,
  `maVanDon` varchar(50) DEFAULT NULL,
  `maSP` varchar(50) NOT NULL,
  `tenSanPham` varchar(50) NOT NULL,
  `soLuong` int(11) NOT NULL,
  `khoiLuong` decimal(10,2) NOT NULL,
  `Dai` decimal(10,2) DEFAULT NULL,
  `Rong` decimal(10,2) DEFAULT NULL,
  `giaTri` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `sanpham`
--

INSERT INTO `sanpham` (`id_sanPham`, `maVanDon`, `maSP`, `tenSanPham`, `soLuong`, `khoiLuong`, `Dai`, `Rong`, `giaTri`) VALUES
(5, 'DGH20250510061257', '1234', 'quần ', 1, 200.00, NULL, NULL, NULL),
(6, 'DGH20250510061807', 'DHH123456', 'Áo', 1, 200.00, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `thanhtoan`
--

CREATE TABLE `thanhtoan` (
  `id_thanhToan` int(11) NOT NULL,
  `maVanDon` varchar(40) NOT NULL,
  `phuongThuc` varchar(50) NOT NULL,
  `trangThai` varchar(20) NOT NULL,
  `ngayThanhToan` datetime DEFAULT NULL,
  `soTien` int(11) NOT NULL,
  `nguoiThanhToan` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `thongbao`
--

CREATE TABLE `thongbao` (
  `id_thongBao` int(11) NOT NULL,
  `id_NhanVien` int(11) NOT NULL,
  `noiDung` text NOT NULL,
  `ngayTao` datetime NOT NULL,
  `trangThai` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `thongbao`
--

INSERT INTO `thongbao` (`id_thongBao`, `id_NhanVien`, `noiDung`, `ngayTao`, `trangThai`) VALUES
(1, 2, 'Bạn được phân công đơn hàng DGH20250510061807', '2025-05-10 11:36:01', 'Đã đọc'),
(9, 2, '🗓 Lịch làm việc ngày 2025-05-09 từ 06:00:00 đến 17:00:00 của bạn đã được cập nhật.', '2025-05-10 08:27:00', 'Đã đọc'),
(10, 2, 'Đơn hàng DGH20250510061807 đã được cập nhật trạng thái: Đang giao', '2025-05-10 14:42:07', 'Đã đọc'),
(11, 2, 'Đơn hàng DGH20250510061807 đã được cập nhật trạng thái: Đang giao', '2025-05-10 14:43:13', 'Đã đọc'),
(12, 2, 'Đơn hàng DGH20250510061807 đã được cập nhật trạng thái: Đang giao', '2025-05-10 14:44:18', 'Đã đọc'),
(13, 2, 'Đơn hàng DGH20250510061807 đã được cập nhật trạng thái: Đã giao', '2025-05-10 14:45:54', 'Đã đọc'),
(14, 2, 'Đơn hàng DGH20250510061257 đã được cập nhật trạng thái: Đang giao', '2025-05-10 14:53:11', 'Đã đọc'),
(15, 2, 'Đơn hàng DGH20250510061257 đã được cập nhật trạng thái: Đang giao', '2025-05-10 14:59:59', 'Đã đọc');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `trangthai`
--

CREATE TABLE `trangthai` (
  `id_trangThai` int(11) NOT NULL,
  `tenTrangThai` varchar(100) NOT NULL,
  `moTa` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `trangthai`
--

INSERT INTO `trangthai` (`id_trangThai`, `tenTrangThai`, `moTa`) VALUES
(1, 'Đã tạo', 'Đơn hàng đã được tạo'),
(2, 'Đang xử lý', 'Đơn hàng đang được xử lý'),
(3, 'Đang giao', 'Đơn hàng đang trong quá trình vận chuyển'),
(4, 'Đã giao', 'Đơn hàng đã được giao thành công '),
(5, 'Đã hủy', 'Đơn hàng đã bị hủy');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `vanchuyen`
--

CREATE TABLE `vanchuyen` (
  `id_vanChuyen` int(11) NOT NULL,
  `maVanDon` varchar(40) NOT NULL,
  `id_BuuCuc` int(11) NOT NULL,
  `id_KhoHang` int(11) NOT NULL,
  `ngayXuat` datetime DEFAULT NULL,
  `ngayNhap` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `buucuc`
--
ALTER TABLE `buucuc`
  ADD PRIMARY KEY (`id_buuCuc`);

--
-- Chỉ mục cho bảng `diachi`
--
ALTER TABLE `diachi`
  ADD PRIMARY KEY (`id_diaChi`);

--
-- Chỉ mục cho bảng `doanhthu`
--
ALTER TABLE `doanhthu`
  ADD PRIMARY KEY (`id_doanhThu`);

--
-- Chỉ mục cho bảng `donhang`
--
ALTER TABLE `donhang`
  ADD PRIMARY KEY (`maVanDon`);

--
-- Chỉ mục cho bảng `hoadon`
--
ALTER TABLE `hoadon`
  ADD PRIMARY KEY (`id_hoaDon`);

--
-- Chỉ mục cho bảng `khachhang`
--
ALTER TABLE `khachhang`
  ADD PRIMARY KEY (`id_khachHang`);

--
-- Chỉ mục cho bảng `khohang`
--
ALTER TABLE `khohang`
  ADD PRIMARY KEY (`id_kho`);

--
-- Chỉ mục cho bảng `lichlamviec`
--
ALTER TABLE `lichlamviec`
  ADD PRIMARY KEY (`id_lichLamViec`);

--
-- Chỉ mục cho bảng `lichsu_trangthai`
--
ALTER TABLE `lichsu_trangthai`
  ADD PRIMARY KEY (`id_lichSuTrangThai`);

--
-- Chỉ mục cho bảng `lienhe`
--
ALTER TABLE `lienhe`
  ADD PRIMARY KEY (`id_lienHe`);

--
-- Chỉ mục cho bảng `nguoigui`
--
ALTER TABLE `nguoigui`
  ADD PRIMARY KEY (`id_nguoiGui`);

--
-- Chỉ mục cho bảng `nguoinhan`
--
ALTER TABLE `nguoinhan`
  ADD PRIMARY KEY (`id_nguoiNhan`);

--
-- Chỉ mục cho bảng `nhanvien`
--
ALTER TABLE `nhanvien`
  ADD PRIMARY KEY (`id_nhanVien`);

--
-- Chỉ mục cho bảng `phi`
--
ALTER TABLE `phi`
  ADD PRIMARY KEY (`id_phi`);

--
-- Chỉ mục cho bảng `sanpham`
--
ALTER TABLE `sanpham`
  ADD PRIMARY KEY (`id_sanPham`);

--
-- Chỉ mục cho bảng `thanhtoan`
--
ALTER TABLE `thanhtoan`
  ADD PRIMARY KEY (`id_thanhToan`);

--
-- Chỉ mục cho bảng `thongbao`
--
ALTER TABLE `thongbao`
  ADD PRIMARY KEY (`id_thongBao`);

--
-- Chỉ mục cho bảng `trangthai`
--
ALTER TABLE `trangthai`
  ADD PRIMARY KEY (`id_trangThai`);

--
-- Chỉ mục cho bảng `vanchuyen`
--
ALTER TABLE `vanchuyen`
  ADD PRIMARY KEY (`id_vanChuyen`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `buucuc`
--
ALTER TABLE `buucuc`
  MODIFY `id_buuCuc` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `diachi`
--
ALTER TABLE `diachi`
  MODIFY `id_diaChi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT cho bảng `doanhthu`
--
ALTER TABLE `doanhthu`
  MODIFY `id_doanhThu` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `hoadon`
--
ALTER TABLE `hoadon`
  MODIFY `id_hoaDon` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `khachhang`
--
ALTER TABLE `khachhang`
  MODIFY `id_khachHang` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT cho bảng `khohang`
--
ALTER TABLE `khohang`
  MODIFY `id_kho` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `lichlamviec`
--
ALTER TABLE `lichlamviec`
  MODIFY `id_lichLamViec` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `lichsu_trangthai`
--
ALTER TABLE `lichsu_trangthai`
  MODIFY `id_lichSuTrangThai` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT cho bảng `lienhe`
--
ALTER TABLE `lienhe`
  MODIFY `id_lienHe` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `nguoigui`
--
ALTER TABLE `nguoigui`
  MODIFY `id_nguoiGui` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT cho bảng `nguoinhan`
--
ALTER TABLE `nguoinhan`
  MODIFY `id_nguoiNhan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT cho bảng `nhanvien`
--
ALTER TABLE `nhanvien`
  MODIFY `id_nhanVien` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `phi`
--
ALTER TABLE `phi`
  MODIFY `id_phi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT cho bảng `sanpham`
--
ALTER TABLE `sanpham`
  MODIFY `id_sanPham` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `thanhtoan`
--
ALTER TABLE `thanhtoan`
  MODIFY `id_thanhToan` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `thongbao`
--
ALTER TABLE `thongbao`
  MODIFY `id_thongBao` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT cho bảng `trangthai`
--
ALTER TABLE `trangthai`
  MODIFY `id_trangThai` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `vanchuyen`
--
ALTER TABLE `vanchuyen`
  MODIFY `id_vanChuyen` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

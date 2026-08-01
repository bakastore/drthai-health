DRTHAI HEALTH — BLOCK THEME 2.0.0
=================================

Mục đích
--------
Giao diện khối độc lập cho website WordPress của Thạc sĩ Nguyễn Hồng Thái –
Chuyên khoa Tiêu hóa. Thiết kế dùng phong cách kem ấm, xanh đậm và vàng nhấn
theo file gastrocare-landing-mockup.html; không sao chép mã, logo hay ảnh của
theme thương mại khác.

Cài đặt an toàn
---------------
1. Sao lưu database và wp-content trước khi thay đổi giao diện.
2. Vào Giao diện > Giao diện > Thêm giao diện mới > Tải giao diện lên.
3. Chọn drthai-health.zip và bấm Cài đặt.
4. Dùng Xem trước trực tiếp trước khi Kích hoạt.
5. Nếu cần quay lui, kích hoạt lại Twenty Twenty-Five.

Thiết lập tự động khi kích hoạt
------------------------------
- Không xóa hoặc ghi đè trang, bài viết và media hiện có.
- Chỉ tạo các trang còn thiếu: Trang chủ, Giới thiệu, Chuyên môn, Tin tức,
  Đặt lịch, Liên hệ, Chính sách quyền riêng tư và Miễn trừ trách nhiệm y khoa.
- Đặt /trang-chu/ làm trang chủ tĩnh.
- Chỉ tạo sáu chuyên mục tin tức còn thiếu:
  Dạ dày – Thực quản; Đại tràng – Trực tràng; Gan – Mật – Tụy;
  Nội soi tiêu hóa; Dinh dưỡng tiêu hóa; Phòng bệnh và lối sống.

Tin tức và chủ đề
-----------------
- Trang /tin-tuc/ tự nhóm tối đa ba bài mới nhất theo từng chuyên mục.
- Chuyên mục được quản lý tại Bài viết > Chuyên mục.
- Chủ đề được quản lý bằng Thẻ tại Bài viết > Thẻ.
- Khi tạo bài mới, gắn một hoặc nhiều Chuyên mục và Thẻ; trang Tin tức,
  trang lưu trữ và trang chủ sẽ tự cập nhật.
- Theme không tự tạo hoặc xuất bản bài viết y khoa mẫu.

Biểu mẫu liên hệ
----------------
- Có tại /lien-he/, /dat-lich/ và khu vực mở đầu trang chủ.
- Chỉ thu: họ tên, số điện thoại, email tùy chọn và thời gian muốn nhận cuộc gọi.
- Có Nonce (mã xác thực), Honeypot (bẫy chống bot), kiểm tra dữ liệu phía máy chủ
  và Rate limit (giới hạn tần suất) 5 phút theo số điện thoại.
- Yêu cầu hợp lệ được lưu riêng tại Dashboard > Yêu cầu gọi lại và đồng thời
  thử gửi email tới địa chỉ quản trị WordPress. Email chỉ hoạt động khi máy chủ
  đã cấu hình gửi mail/SMTP.
- Không có trường triệu chứng, bệnh án hoặc dữ liệu sức khỏe.

Quản trị dữ liệu cá nhân
-----------------------
Thông tin form là dữ liệu cá nhân. Trước khi public website cần hoàn thiện Chính
sách quyền riêng tư, phân quyền tài khoản quản trị, HTTPS, thời hạn lưu/xóa yêu
cầu, backup mã hóa và quy trình xử lý yêu cầu của chủ thể dữ liệu.

Nội dung cần xác nhận trước khi công khai
----------------------------------------
- Sáu nhóm chuyên môn/dịch vụ trên trang chủ là nội dung dự kiến.
- Ảnh bác sĩ hiện là minh họa SVG; cần thay bằng ảnh thật được phép sử dụng.
- Cần bổ sung nơi công tác, lịch khám, phạm vi hành nghề và thông tin pháp lý.
- Kiểm tra lại số điện thoại, email và địa điểm.

Responsive Design (thiết kế thích ứng)
--------------------------------------
Theme có breakpoint (điểm chuyển bố cục) cho desktop, tablet và mobile. Menu
chuyển sang dạng phủ trên mobile; lưới bài viết 3 cột chuyển 2 rồi 1 cột; form
2 cột chuyển 1 cột; nút và trường nhập liệu đủ rộng để thao tác cảm ứng.

Tùy chỉnh
---------
Vào Giao diện > Trình biên tập để chỉnh Đầu trang, Chân trang, Mẫu và Kiểu.
Màu, font và khoảng cách được quản lý tập trung trong theme.json.

Giấy phép
---------
GNU General Public License v2 hoặc mới hơn. Ảnh SVG trong assets/images được
tạo riêng cho gói, không lấy từ website mẫu hoặc kho ảnh bên ngoài.

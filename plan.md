# Lộ trình xây dựng Todo Dashboard bằng Laravel Blade + AJAX

## 1. Mục tiêu cuối cùng

Xây dựng một **Todo Dashboard cá nhân**, không phải hệ thống quản trị toàn bộ người dùng. Sau khi hoàn thành, một người dùng có thể:

- Đăng nhập và đăng xuất bằng session/cookie của Laravel.
- Xem danh sách Todo thuộc về chính mình.
- Tạo Todo mới bằng AJAX.
- Mở màn hình chi tiết/chỉnh sửa một Todo.
- Cập nhật tiêu đề và trạng thái hoàn thành bằng AJAX.
- Xóa Todo của chính mình.
- Không thể xem, sửa hoặc xóa Todo của người khác.
- Nhận được thông báo và lỗi validation rõ ràng mà không phải tải lại toàn bộ trang.

Kiến trúc giao diện mong muốn:

```text
Browser
  ├── GET page route ──> Controller ──> Blade view (khung HTML)
  └── AJAX request ────> Controller ──> Service/Eloquent ──> JSON
```

Ứng dụng vẫn là một Laravel monolith. Không thêm Vue, React hoặc một frontend project riêng.

## 2. Nguyên tắc dùng lộ trình

- Thực hiện tuần tự từ Bước 1 đến Bước 10.
- Mỗi cuộc trò chuyện mới chỉ thực hiện **một bước**.
- Trước khi sửa, luôn yêu cầu kiểm tra source hiện tại vì repo có thể đã thay đổi sau bước trước.
- Không làm trước nội dung của bước tiếp theo.
- Giải thích bằng một request flow cụ thể trước, sau đó mới giải thích khái niệm Laravel liên quan.
- Phân biệt rõ điều đã chứng minh bằng source, test và runtime.
- Giữ lại API Sanctum hiện tại trừ khi một bước có lý do rõ ràng để thay đổi nó.
- Không thêm package authentication hoặc UI framework nếu chức năng có thể làm rõ ràng bằng Laravel core, Blade và JavaScript hiện có.
- Sau mỗi bước, chạy formatter/test phù hợp nếu môi trường cho phép; nếu không, cung cấp chính xác lệnh để người dùng tự chạy.

## Bước 1 — Khảo sát dự án và hiểu request lifecycle hiện tại

### Mục tiêu học

Hiểu một request Todo hiện tại đi qua Laravel như thế nào trước khi xây giao diện.

### Nội dung thực hiện

- Kiểm tra phiên bản Laravel/PHP trong `composer.json` và môi trường chạy thực tế.
- Đọc `routes/api.php`, `routes/web.php` và các lớp Todo/Auth liên quan.
- Trace ít nhất hai flow đang có:
  - `POST /api/login`.
  - `GET /api/todos`.
- Giải thích vai trò của route, middleware, FormRequest, controller, service interface, service implementation, model và JsonResource.
- Ghi nhận những chức năng còn thiếu cho Dashboard: web login/logout, Blade pages, show/update/delete và authorization theo ownership.
- Không sửa code trong bước này.

### Kết quả cần đạt

- Có sơ đồ request flow dựa trên source thật.
- Biết phần nào có thể tái sử dụng và phần nào cần bổ sung.
- Hiểu API token hiện tại khác session authentication như thế nào.

### Prompt dùng cho cuộc trò chuyện mới

> Thực hiện Bước 1 trong `plan.md`: khảo sát source hiện tại và giải thích request lifecycle của login và Todo API. Chỉ phân tích, chưa sửa code. Hãy trace từ route đến response, chỉ rõ phần nào đã có và phần nào còn thiếu cho Todo Dashboard.

## Bước 2 — Thiết lập nền tảng kiểm thử cho luồng Todo

### Mục tiêu học

Hiểu cách Laravel tạo dữ liệu test, gửi HTTP request và kiểm tra authentication/database.

### Nội dung thực hiện

- Kiểm tra cấu hình test và database test hiện tại.
- Tạo hoặc hoàn thiện `TodoFactory` nếu chưa có.
- Tạo quan hệ `User`–`Todo` cần thiết nếu source chưa định nghĩa đầy đủ.
- Viết feature test bảo vệ hành vi API hiện tại trước khi thêm web UI:
  - Guest không truy cập được Todo API.
  - User chỉ nhận Todo của mình.
  - User có thể tạo Todo hợp lệ.
  - Payload sai nhận lỗi validation.
- Không xây giao diện hoặc session login trong bước này.

### Kết quả cần đạt

- Có test làm safety net cho hành vi hiện tại.
- Hiểu factory, `RefreshDatabase`, authenticated request và JSON assertions.

### Prompt dùng cho cuộc trò chuyện mới

> Thực hiện Bước 2 trong `plan.md`: bổ sung nền tảng feature test cho Todo API hiện tại. Trước khi sửa hãy kiểm tra source và test đang có. Chỉ làm đúng phạm vi bước này, giải thích request flow của từng test và không bắt đầu phần Blade/session login.

## Bước 3 — Xây dựng đăng nhập/đăng xuất bằng web session

### Mục tiêu học

Hiểu `web` middleware, session cookie, CSRF và sự khác nhau giữa web authentication với Sanctum personal access token.

### Nội dung thực hiện

- Tạo route hiển thị trang đăng nhập.
- Tạo route xử lý login bằng email/password và tạo authenticated session.
- Regenerate session sau khi login thành công.
- Tạo logout dùng `POST`, invalidate session và regenerate CSRF token.
- Tạo Blade login cực đơn giản, hiển thị validation/authentication errors.
- Redirect hợp lý giữa guest, login và Todo Dashboard placeholder.
- Giữ nguyên `POST /api/login` để có thể so sánh hai cơ chế authentication.
- Viết feature test cho login thành công, login thất bại, guest redirect và logout.

### Luồng trọng tâm

```text
GET /login
→ Blade form
→ POST /login + CSRF
→ validate credentials
→ Auth::attempt
→ regenerate session
→ redirect dashboard
```

### Kết quả cần đạt

- Browser đăng nhập bằng session, không lưu API token trong `localStorage`.
- Hiểu vì sao AJAX cùng origin tự gửi session cookie.

### Prompt dùng cho cuộc trò chuyện mới

> Thực hiện Bước 3 trong `plan.md`: xây dựng web session login/logout và Blade login tối giản, đồng thời giữ nguyên API Sanctum hiện tại. Hãy giải thích rõ cookie session, CSRF và middleware `guest`/`auth`; chỉ làm phạm vi authentication của bước này.

## Bước 4 — Tạo Todo list bằng Blade server-rendered trước

### Mục tiêu học

Hiểu route web, controller, Blade layout và cách truyền dữ liệu từ Laravel sang view trước khi thêm AJAX.

### Nội dung thực hiện

- Tạo authenticated route cho màn hình danh sách Todo.
- Tạo controller action lấy Todo của user đang đăng nhập.
- Tái sử dụng service hiện tại khi phù hợp, không duplicate query tùy tiện.
- Tạo layout Blade chung và trang Todo list.
- Render danh sách trực tiếp bằng Blade (`@forelse`, escaping output, route helpers).
- Hiển thị empty state khi chưa có Todo.
- Viết feature test xác nhận guest bị redirect và user chỉ nhìn thấy Todo của mình.
- Chưa tạo Todo bằng AJAX và chưa làm màn hình edit.

### Luồng trọng tâm

```text
GET /todos
→ auth middleware
→ controller
→ service/query theo current user
→ view('todos.index', data)
→ HTML response
```

### Kết quả cần đạt

- Có một trang list hoạt động hoàn toàn không cần JavaScript.
- Hiểu Blade đang render HTML ở server, khác với JsonResource trả JSON.

### Prompt dùng cho cuộc trò chuyện mới

> Thực hiện Bước 4 trong `plan.md`: xây Todo list server-rendered bằng Blade cho user đã đăng nhập. Tái sử dụng business logic hiện tại khi hợp lý, viết test ownership cho danh sách, chưa dùng AJAX và chưa làm create/edit/delete.

## Bước 5 — Chuyển việc tải danh sách sang AJAX

### Mục tiêu học

Hiểu mô hình Blade shell + AJAX data và cách Laravel chọn HTML hoặc JSON response.

### Nội dung thực hiện

- Giữ `GET /todos` để trả Blade shell.
- Tạo một web JSON endpoint lấy danh sách Todo cho session-authenticated user.
- Dùng `TodoResource` hoặc response contract nhất quán thay vì tự ghép JSON ở nhiều nơi.
- Viết JavaScript gọi endpoint sau khi trang load và render các hàng Todo.
- Có loading, empty và generic error state tối thiểu.
- Không nhúng API token vào Blade/JavaScript.
- Bảo vệ output khỏi XSS; không đưa dữ liệu người dùng vào `innerHTML` thiếu kiểm soát.
- Viết test cho JSON endpoint và ownership.

### Luồng trọng tâm

```text
GET /todos → Blade shell
Browser JS → GET /todos/data + session cookie
Laravel → JSON resource collection
Browser JS → render list
```

### Kết quả cần đạt

- Hiểu một màn hình có thể dùng đồng thời Blade và AJAX.
- Refresh dữ liệu không cần reload toàn bộ page.

### Prompt dùng cho cuộc trò chuyện mới

> Thực hiện Bước 5 trong `plan.md`: chuyển phần dữ liệu của Todo list sang mô hình Blade shell + AJAX JSON endpoint. Không dùng API token trong browser. Giải thích rõ hai request riêng biệt, xử lý loading/empty/error/XSS và viết test cho endpoint.

## Bước 6 — Tạo Todo bằng AJAX và xử lý validation

### Mục tiêu học

Hiểu CSRF cho AJAX, FormRequest validation và HTTP `422` trong Laravel.

### Nội dung thực hiện

- Thêm form tạo Todo trên trang list.
- Gửi AJAX request bằng session cookie và CSRF token.
- Tái sử dụng hoặc tách FormRequest phù hợp với web endpoint.
- Controller chỉ điều phối; logic tạo Todo tiếp tục nằm ở service.
- Khi thành công, cập nhật lại danh sách mà không reload page.
- Khi `422`, hiển thị lỗi cạnh field tương ứng.
- Ngăn double-submit cơ bản trong lúc request đang chạy.
- Viết test create thành công, validation thất bại và ownership.

### Luồng trọng tâm

```text
Submit form
→ AJAX POST + CSRF
→ auth middleware
→ FormRequest
→ controller
→ service
→ TodoResource JSON
→ update UI
```

### Kết quả cần đạt

- Hiểu validation xảy ra trước controller như thế nào.
- Phân biệt `401`, `419` và `422` trong luồng browser.

### Prompt dùng cho cuộc trò chuyện mới

> Thực hiện Bước 6 trong `plan.md`: thêm chức năng tạo Todo bằng AJAX trên trang list. Tập trung vào CSRF, FormRequest, response `422`, chống double-submit và cập nhật UI không reload. Viết feature test nhưng chưa làm edit hoặc delete.

## Bước 7 — Tạo trang detail/edit và cập nhật Todo bằng AJAX

### Mục tiêu học

Hiểu route model binding, form update, method semantics và cách tải một resource vào Blade page.

### Nội dung thực hiện

- Tạo URL riêng cho màn hình edit, ví dụ `/todos/{todo}/edit`.
- Dùng route model binding thay vì tự đọc ID từ request rồi query tùy tiện.
- Tạo Blade edit page hiển thị dữ liệu Todo hiện tại.
- Tạo `UpdateTodoRequest` với rule cho `title` và `completed`.
- Bổ sung method update vào controller/service/interface hiện tại.
- Submit form bằng AJAX với `PUT` hoặc `PATCH`.
- Hiển thị validation errors và trạng thái lưu thành công/thất bại.
- Viết test mở edit page và update thành công/thất bại.
- Authorization đầy đủ sẽ được tập trung ở Bước 8; tuy nhiên không được để lộ Todo người khác trong thời gian chuyển tiếp.

### Luồng trọng tâm

```text
GET /todos/{todo}/edit
→ route model binding
→ authorization
→ Blade edit page

PATCH /todos/{todo}
→ FormRequest
→ authorization
→ service update
→ TodoResource JSON
```

### Kết quả cần đạt

- Có màn hình edit detail đúng nghĩa, không phải modal bắt buộc.
- Hiểu route model binding chỉ tìm model; nó không tự chứng minh quyền sở hữu.

### Prompt dùng cho cuộc trò chuyện mới

> Thực hiện Bước 7 trong `plan.md`: tạo Blade edit/detail page và cập nhật Todo bằng AJAX. Dùng route model binding, `UpdateTodoRequest`, service/interface hiện tại và JSON response nhất quán. Giải thích rõ GET page flow và PATCH AJAX flow; chưa mở rộng ngoài phạm vi edit/update.

## Bước 8 — Chuẩn hóa authorization bằng Todo Policy

### Mục tiêu học

Phân biệt authentication với authorization và bảo vệ ownership ở mọi entry point.

### Nội dung thực hiện

- Tạo `TodoPolicy` cho các thao tác cần thiết như view, update và delete.
- Áp dụng authorization nhất quán ở controller/FormRequest phù hợp.
- Kiểm tra cả web page routes, AJAX endpoints và API endpoints liên quan.
- Quyết định rõ response khi truy cập Todo người khác (`403` hay che giấu bằng `404`) và dùng nhất quán.
- Không chỉ dựa vào việc ẩn nút trên giao diện.
- Viết test với ít nhất hai user để chứng minh user A không thể xem/sửa/xóa Todo của user B.

### Luồng trọng tâm

```text
Authenticated user
→ route model binding tìm Todo
→ Policy kiểm tra owner
→ cho phép action hoặc trả 403/404
```

### Kết quả cần đạt

- Mọi thao tác trên một Todo đều có kiểm tra quyền phía server.
- Hiểu `auth` trả lời “bạn là ai”, Policy trả lời “bạn được làm gì”.

### Prompt dùng cho cuộc trò chuyện mới

> Thực hiện Bước 8 trong `plan.md`: chuẩn hóa ownership bằng `TodoPolicy` cho toàn bộ flow hiện có. Audit web routes, AJAX endpoints và API liên quan; viết test hai user chứng minh không thể đọc hoặc thay đổi Todo của nhau. Không dựa vào kiểm tra phía UI.

## Bước 9 — Xóa Todo và hoàn thiện các trạng thái UI

### Mục tiêu học

Hoàn thành CRUD và thiết kế UI state dựa trên HTTP response thực tế.

### Nội dung thực hiện

- Thêm delete action qua AJAX với confirmation đơn giản.
- Bảo vệ delete bằng Policy và CSRF.
- Xử lý response thành công phù hợp, ví dụ `204 No Content`.
- Sau delete, cập nhật list/redirect hợp lý mà không tạo trạng thái UI sai.
- Chuẩn hóa cách frontend xử lý:
  - `401`: session không còn hợp lệ.
  - `403` hoặc `404`: không có quyền truy cập/resource.
  - `419`: CSRF/session hết hạn.
  - `422`: validation error.
  - `500`: lỗi ngoài dự kiến.
- Hoàn thiện disabled/loading/success/error state cho create và update.
- Viết test delete thành công, forbidden/not-found và response contract.

### Kết quả cần đạt

- Hoàn chỉnh CRUD cho Todo cá nhân.
- Frontend không coi mọi lỗi HTTP là cùng một loại lỗi.

### Prompt dùng cho cuộc trò chuyện mới

> Thực hiện Bước 9 trong `plan.md`: thêm delete bằng AJAX và hoàn thiện loading/success/error states cho CRUD hiện có. Xử lý rõ các mã 401, 403/404, 419, 422 và 500; bảo vệ ownership/CSRF và bổ sung feature test tương ứng.

## Bước 10 — Kiểm thử end-to-end, rà soát kiến trúc và hoàn thiện

### Mục tiêu học

Biết cách chứng minh một feature Laravel hoạt động xuyên suốt và nhận ra phần nào đang bị thiết kế quá mức.

### Nội dung thực hiện

- Chạy toàn bộ formatter và test suite.
- Bổ sung test còn thiếu cho happy path và các boundary quan trọng.
- Kiểm tra thủ công flow hoàn chỉnh:
  - Guest mở dashboard.
  - Login đúng/sai.
  - List/create/edit/update/delete.
  - Logout.
  - User A thử truy cập Todo của user B.
- Audit route/middleware bằng công cụ Laravel phù hợp.
- Rà soát CSRF, escaping/XSS, mass assignment và ownership.
- Rà soát query thừa/N+1 nhưng chỉ tối ưu khi có bằng chứng.
- Xóa code chết hoặc duplicate phát sinh trong quá trình học, không refactor ngoài phạm vi.
- Cập nhật README ngắn gọn về cách chạy và kiến trúc request flow nếu cần.
- Tổng kết sự khác nhau giữa:
  - `web.php` và `api.php`.
  - Session cookie và Sanctum personal access token.
  - Blade HTML response và JsonResource JSON response.
  - Authentication và authorization.
  - Validation và business logic.

### Kết quả cần đạt

- Test suite vượt qua.
- Flow chính được kiểm tra ở runtime, không chỉ suy luận từ source.
- Có thể tự trace một lỗi từ browser qua route/controller/service/database và quay lại response.

### Prompt dùng cho cuộc trò chuyện mới

> Thực hiện Bước 10 trong `plan.md`: kiểm thử và audit toàn bộ Todo Dashboard sau các bước trước. Chạy formatter/tests, kiểm tra runtime flow nếu môi trường cho phép, rà soát security/ownership/CSRF/XSS và chỉ sửa các vấn đề thuộc feature này. Cuối cùng tổng kết kiến thức Laravel đã dùng và phân biệt source/test/runtime evidence.

## 3. Definition of Done toàn bộ lộ trình

Lộ trình chỉ hoàn thành khi đáp ứng đồng thời:

- Guest không thể vào các trang Todo.
- User đăng nhập/đăng xuất bằng web session an toàn.
- Blade đảm nhiệm page shell; AJAX đảm nhiệm các thao tác dữ liệu cần cập nhật động.
- User chỉ đọc và thay đổi Todo của chính mình.
- Create, update và delete có validation/authorization phía server.
- AJAX gửi CSRF đúng và xử lý các HTTP error quan trọng.
- Feature tests bao phủ authentication, validation, ownership và CRUD.
- API Sanctum cũ không bị phá ngoài chủ đích.
- Formatter và test suite chạy thành công.
- Runtime flow đã được kiểm tra riêng, không suy luận rằng test pass đồng nghĩa browser chắc chắn hoạt động.

## 4. Những phần cố ý chưa làm

Để giữ dự án phù hợp cho người đang học Laravel, lộ trình này chưa bao gồm:

- Vai trò admin quản lý tất cả người dùng.
- Đăng ký tài khoản, quên mật khẩu hoặc xác minh email.
- Vue, React, Livewire hoặc Inertia.
- DataTables, dashboard template hoặc UI framework lớn.
- Todo sharing, team/workspace hoặc phân quyền nhiều vai trò.
- Search nâng cao, sorting phức tạp hoặc realtime WebSocket.
- Deploy production.

Những phần này chỉ nên bổ sung sau khi flow cơ bản đã hoàn thành và có nhu cầu nghiệp vụ rõ ràng.

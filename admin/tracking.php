<?php
// tracking.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require '../config/conn.php';
require 'header.php';

// Hàm tính khoảng cách giữa 2 toạ độ GPS (Haversine)
function calculateDistance($lat1, $lng1, $lat2, $lng2) {
    $earthRadius = 6371; // km
    $lat1 = deg2rad($lat1);
    $lng1 = deg2rad($lng1);
    $lat2 = deg2rad($lat2);
    $lng2 = deg2rad($lng2);

    $dLat = $lat2 - $lat1;
    $dLng = $lng2 - $lng1;

    $a = sin($dLat/2) * sin($dLat/2) +
         cos($lat1) * cos($lat2) *
         sin($dLng/2) * sin($dLng/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));

    return $earthRadius * $c;
}

// Lấy danh sách toàn bộ nhân viên giao hàng
$sqlStaff = "SELECT id_nhanVien, tenNhanVien, viTriLat, viTriLng FROM nhanvien WHERE viTri = 'Giao hàng'";
$resultStaff = $mysqli->query($sqlStaff);

// Xử lý lọc theo bán kính
$filteredStaff = [];
$latCenter = isset($_GET['lat']) ? (float)$_GET['lat'] : null;
$lngCenter = isset($_GET['lng']) ? (float)$_GET['lng'] : null;
$radius    = isset($_GET['radius']) ? (float)$_GET['radius'] : 5; // km mặc định

if ($latCenter !== null && $lngCenter !== null) {
    foreach ($resultStaff as $st) {
        if ($st['viTriLat'] !== null && $st['viTriLng'] !== null) {
            $dist = calculateDistance($latCenter, $lngCenter, $st['viTriLat'], $st['viTriLng']);
            if ($dist <= $radius) {
                $filteredStaff[] = $st;
            }
        }
    }
} else {
    // không lọc nếu chưa chọn
    while ($row = $resultStaff->fetch_assoc()) {
        $filteredStaff[] = $row;
    }
}

// Xử lý khi chọn nhân viên để xem chi tiết
$selectedStaff = null;
$orders = [];
if (isset($_GET['staff_id'])) {
    $sid = (int)$_GET['staff_id'];
    $infoStmt = $mysqli->prepare("SELECT tenNhanVien, viTriLat, viTriLng FROM nhanvien WHERE id_nhanVien=?");
    $infoStmt->bind_param('i', $sid);
    $infoStmt->execute();
    $selectedStaff = $infoStmt->get_result()->fetch_assoc();

    $orderStmt = $mysqli->prepare(
        "SELECT D.maVanDon, N.tenNguoiNhan, DC.diaChiNguoiNhan AS diaChiNhan, 
                D.ngayGiao, T.tenTrangThai as trangThaiDonHang,
                D.ngayTaoDon, T.id_trangThai
         FROM donhang D
         JOIN nguoinhan N ON D.id_nguoiNhan = N.id_nguoiNhan
         JOIN diachi DC ON N.id_diaChi = DC.id_diaChi
         JOIN trangthai T ON D.id_trangThai = T.id_trangThai
         WHERE D.id_nhanVien=? AND T.tenTrangThai IN ('Đang xử lý', 'Đang giao')"
    );
    $orderStmt->bind_param('i', $sid);
    $orderStmt->execute();
    $orders = $orderStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Theo dõi giao hàng</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Leaflet CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
  <link rel="stylesheet" href="./css/tracking.css">
</head>
<body>
  <div class="content-container">
    <h2>Theo dõi giao hàng</h2>

    <!-- Bản đồ chọn trung tâm và bán kính -->
    <div id="filterMap"></div>
    <div class="controls">
      <label>Bán kính (km): <input type="range" id="radius" min="1" max="50" value="<?= $radius ?>"> <span id="radiusVal"><?= $radius ?></span> km</label>
      <button id="applyFilter" class="btn">Áp dụng lọc</button>
      <button id="resetMap" class="btn" style="background: #0dcaf0;">Vị trí bưu cục</button>
    </div>

    <?php if (empty($filteredStaff)): ?>
      <div class="notification">Không tìm thấy nhân viên trong khu vực đã chọn</div>
    <?php else: ?>
      <table>
        <thead><tr><th>Nhân viên giao</th><th>Lat</th><th>Lng</th><th>Hành động</th></tr></thead>
        <tbody>
        <?php foreach ($filteredStaff as $st): ?>
          <tr>
            <td><?= htmlspecialchars($st['tenNhanVien']) ?></td>
            <td><?= $st['viTriLat'] ?></td>
            <td><?= $st['viTriLng'] ?></td>
            <td><a class="btn" href="?staff_id=<?= $st['id_nhanVien'] ?>&lat=<?= urlencode($latCenter) ?>&lng=<?= urlencode($lngCenter) ?>&radius=<?= urlencode($radius) ?>">Xem tiến độ</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>

      <?php if ($selectedStaff): ?>
        <div class="detail-section">
          <h3>Vị trí hiện tại của <?= htmlspecialchars($selectedStaff['tenNhanVien']) ?>:</h3>
          <?php if (!empty($selectedStaff['viTriLat'])): ?>
            <iframe class="map-frame" src="https://maps.google.com/maps?q=<?= urlencode($selectedStaff['viTriLat']) ?>,<?= urlencode($selectedStaff['viTriLng']) ?>&hl=vi&z=15&output=embed" allowfullscreen></iframe>
          <?php else: ?>
            <p>Chưa có dữ liệu vị trí GPS.</p>
          <?php endif; ?>

          <h3>Danh sách đơn đang giao:</h3>
          <?php if (empty($orders)): ?>
            <div class="notification">Không có đơn hàng đang giao</div>
          <?php else: ?>
            <table>
              <thead><tr><th>Mã đơn</th><th>Người nhận</th><th>Địa chỉ</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
              <tbody>
              <?php foreach ($orders as $o): ?>
                <tr>
                  <td><?= htmlspecialchars($o['maVanDon']) ?></td>
                  <td><?= htmlspecialchars($o['tenNguoiNhan']) ?></td>
                  <td><?= htmlspecialchars($o['diaChiNhan']) ?></td>
                  <td>
                    <?php if ($o['id_trangThai'] == 2): // Đang xử lý ?>
                      Đang xử lý
                    <?php elseif ($o['id_trangThai'] == 3): // Đang giao ?>
                      <?= $o['ngayGiao'] ? date('d/m/Y', strtotime($o['ngayGiao'])) : 'Đang giao' ?>
                    <?php endif; ?>
                  </td>
                  <td><button class="btn view-order" 
                      data-ma="<?= htmlspecialchars($o['maVanDon']) ?>" 
                      data-recipient="<?= htmlspecialchars($o['tenNguoiNhan']) ?>" 
                      data-address="<?= htmlspecialchars($o['diaChiNhan']) ?>" 
                      data-date="<?= $o['ngayGiao'] ? htmlspecialchars(date('d/m/Y', strtotime($o['ngayGiao']))) : 'Chưa có ngày giao' ?>" 
                      data-status="<?= htmlspecialchars($o['trangThaiDonHang']) ?>"
                      data-created="<?= htmlspecialchars(date('d/m/Y', strtotime($o['ngayTaoDon']))) ?>">Xem</button></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <!-- Modal chi tiết đơn hàng -->
  <div id="orderModal" class="modal">
    <div class="modal-content">
      <span class="close" id="closeOrder">&times;</span>
      <div class="modal-header">Chi tiết đơn hàng</div>
      <div class="modal-body" id="orderModalBody"></div>
    </div>
  </div>

  <!-- Leaflet JS -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
  

<script>
document.addEventListener('DOMContentLoaded', () => {
  const defaultLat  = 10.78164054437323,
        defaultLng  = 106.70532567988505,
        latCenter   = <?= $latCenter ?? 'null' ?>,
        lngCenter   = <?= $lngCenter ?? 'null' ?>,
        initialZoom = 13;

  // Biến chứa marker mỗi nhân viên
  let staffMarkers = {};

  // Dữ liệu nhân viên từ PHP
  const staffData = <?= json_encode($filteredStaff, JSON_HEX_TAG) ?>;

  // Khởi tạo map
  const map = L.map('filterMap').setView(
    [latCenter || defaultLat, lngCenter || defaultLng],
    initialZoom
  );
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);

  // Icon cho nhân viên
  const empIcon = L.icon({
    iconUrl: 'img/icon-shipper.png',
    iconSize: [32,32],
    iconAnchor: [16,32],
    popupAnchor: [0,-32],
  });

  // Vẽ marker ban đầu cho mỗi nhân viên
  staffData.forEach(st => {
    if (st.viTriLat && st.viTriLng) {
      const m = L.marker([+st.viTriLat, +st.viTriLng], { icon: empIcon })
        .addTo(map)
        .bindPopup(`<b>${st.tenNhanVien}</b><br>Lat: ${(+st.viTriLat).toFixed(5)}<br>Lng: ${(+st.viTriLng).toFixed(5)}`);
      staffMarkers[st.id_nhanVien] = m;
    }
  });

  // Marker trung tâm & vòng tròn bán kính
  const centerMarker = L.marker(
    [latCenter || defaultLat, lngCenter || defaultLng],
    { draggable: true }
  ).addTo(map);

  const circle = L.circle(centerMarker.getLatLng(), {
    radius: <?= $radius ?> * 1000,
    color: '#1abc9c',
    fillColor: '#1abc9c',
    fillOpacity: 0.2
  }).addTo(map);

  // Khi kéo marker trung tâm
  centerMarker.on('move', e => circle.setLatLng(e.latlng));
  centerMarker.on('dragend', () => map.panTo(centerMarker.getLatLng()));

  // Điều khiển bán kính
  const radiusInput = document.getElementById('radius'),
        radiusVal   = document.getElementById('radiusVal');
  radiusInput.addEventListener('input', e => {
    const r = e.target.value * 1000;
    circle.setRadius(r);
    radiusVal.innerText = e.target.value;
  });

  // Click bản đồ để đặt tâm
  map.on('click', e => {
  //   const lat = e.latlng.lat.toFixed(6); // vĩ độ
  // const lng = e.latlng.lng.toFixed(6); // kinh độ

  // alert(`Tọa độ bạn vừa click:\nLatitude: ${lat}\nLongitude: ${lng}`);
    centerMarker.setLatLng(e.latlng);
    circle.setLatLng(e.latlng);
    map.panTo(e.latlng);
  });

  // Áp dụng lọc (reload với params mới)
  document.getElementById('applyFilter').addEventListener('click', () => {
    const c      = centerMarker.getLatLng();
    const params = new URLSearchParams(location.search);
    params.set('lat', c.lat);
    params.set('lng', c.lng);
    params.set('radius', radiusInput.value);
    const sid = new URLSearchParams(location.search).get('staff_id');
    if (sid) params.set('staff_id', sid);
    location.search = params.toString();
  });

  // Reset map về bưu cục
  document.getElementById('resetMap').addEventListener('click', () => {
    centerMarker.setLatLng([defaultLat, defaultLng]);
    circle.setLatLng([defaultLat, defaultLng]);
    map.setView([defaultLat, defaultLng], initialZoom);
    radiusInput.value = 5;
    radiusVal.innerText = '5';
    circle.setRadius(5000);
  });

  // === WebSocket real-time ===
  const socket = new WebSocket('ws://localhost:8081');
  socket.addEventListener('open', () => console.log('WS connected'));
  socket.addEventListener('error', e => console.error('WS error', e));
  socket.addEventListener('close', () => console.log('WS closed'));

  socket.addEventListener('message', evt => {
    try {
      const data = JSON.parse(evt.data);
      console.log('Received WebSocket data:', data); // Debug log
      
      // Ensure data has required fields
      if (!data.staffId || !data.lat || !data.lng) {
        console.error('Invalid data format:', data);
        return;
      }

      // Convert to numbers
      const staffId = parseInt(data.staffId);
      const lat = parseFloat(data.lat);
      const lng = parseFloat(data.lng);

      // Update marker position
      const m = staffMarkers[staffId];
      if (m) {
        console.log(`Updating marker for staff ${staffId} to lat: ${lat}, lng: ${lng}`);
        
        // Update marker position
        m.setLatLng([lat, lng]);
        
        // Update popup content with new coordinates
        const staffName = staffData.find(s => s.id_nhanVien == staffId)?.tenNhanVien || 'Unknown';
        m.setPopupContent(`<b>${staffName}</b><br>Lat: ${lat.toFixed(5)}<br>Lng: ${lng.toFixed(5)}`);
        
        // Save new location to database
        console.log('Sending update request with data:', {id: staffId, lat: lat, lng: lng}); // Debug log
        
        // Create FormData object
        const formData = new FormData();
        formData.append('id', staffId);
        formData.append('lat', lat);
        formData.append('lng', lng);

        fetch('update_location.php', {
          method: 'POST',
          body: formData
        })
        .then(response => {
          console.log('Response status:', response.status); // Debug log
          return response.json();
        })
        .then(result => {
          console.log('Update result:', result); // Debug log
          if (!result.success) {
            console.error('Failed to update location:', result.message);
          } else {
            // Update staffData array with new coordinates
            const staffIndex = staffData.findIndex(s => s.id_nhanVien == staffId);
            if (staffIndex !== -1) {
              staffData[staffIndex].viTriLat = lat;
              staffData[staffIndex].viTriLng = lng;
              console.log('Updated staffData:', staffData[staffIndex]);
            }
          }
        })
        .catch(error => {
          console.error('Error updating location:', error);
        });
      } else {
        console.warn(`No marker found for staff ID: ${staffId}`);
      }
    } catch (err) {
      console.error('Invalid WS data:', err, 'Raw data:', evt.data);
    }
  });

  // Function to update staff markers
  function updateStaffMarkers() {
    staffData.forEach(st => {
      if (st.viTriLat && st.viTriLng) {
        const lat = parseFloat(st.viTriLat);
        const lng = parseFloat(st.viTriLng);
        
        if (staffMarkers[st.id_nhanVien]) {
          // Update existing marker
          staffMarkers[st.id_nhanVien].setLatLng([lat, lng]);
          staffMarkers[st.id_nhanVien].setPopupContent(
            `<b>${st.tenNhanVien}</b><br>Lat: ${lat.toFixed(5)}<br>Lng: ${lng.toFixed(5)}`
          );
        } else {
          // Create new marker
          const m = L.marker([lat, lng], { icon: empIcon })
            .addTo(map)
            .bindPopup(`<b>${st.tenNhanVien}</b><br>Lat: ${lat.toFixed(5)}<br>Lng: ${lng.toFixed(5)}`);
          staffMarkers[st.id_nhanVien] = m;
        }
      }
    });
  }

  // Initial markers setup
  updateStaffMarkers();

  // Add periodic refresh of staff data
  setInterval(() => {
    fetch('get_staff_locations.php')
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          staffData = data.staff;
          updateStaffMarkers();
        }
      })
      .catch(error => console.error('Error refreshing staff data:', error));
  }, 5000); // Refresh every 5 seconds

  // === Modal chi tiết đơn hàng ===
  // Modal handling with animations
  const orderModal = document.getElementById('orderModal');
      const closeOrderBtn = document.getElementById('closeOrder');
      
      // Add show/hide animations
      function showModal() {
        orderModal.style.display = 'block';
        setTimeout(() => orderModal.classList.add('show'), 10);
      }
      
      function hideModal() {
        orderModal.classList.remove('show');
        setTimeout(() => orderModal.style.display = 'none', 300);
      }
      
      // Order view buttons
      document.querySelectorAll('.view-order').forEach(btn => {
        btn.addEventListener('click', function() {
          document.getElementById('orderModalBody').innerHTML = `
            <ul>
              <li><strong>Mã đơn:</strong> ${this.dataset.ma}</li>
              <li><strong>Người nhận:</strong> ${this.dataset.recipient}</li>
              <li><strong>Địa chỉ:</strong> ${this.dataset.address}</li>
              <li><strong>Trạng thái:</strong> ${this.dataset.status}</li>
            </ul>
          `;
          showModal();
        });
      });
      
      // Close button and outside click
      closeOrderBtn.addEventListener('click', hideModal);
      window.addEventListener('click', function(e) {
        if (e.target === orderModal) {
          hideModal();
        }
      });
      
      // Handle escape key
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && orderModal.style.display === 'block') {
          hideModal();
        }
      });
});
</script>

<?php require 'footer.php'; ?>
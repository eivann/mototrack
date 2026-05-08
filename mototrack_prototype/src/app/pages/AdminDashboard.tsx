import { useState } from 'react';
import { useNavigate } from 'react-router';
import { useAuth } from '../context/AuthContext';
import {
  LayoutDashboard,
  Package,
  Wrench,
  FileText,
  ClipboardCheck,
  LogOut,
  User,
  Bell,
  AlertTriangle,
  TrendingUp,
  ShoppingCart,
} from 'lucide-react';

export function AdminDashboard() {
  const [activeTab, setActiveTab] = useState('overview');
  const { user, logout, isAdmin } = useAuth();
  const navigate = useNavigate();

  // Redirect if not admin
  if (!isAdmin) {
    navigate('/');
    return null;
  }

  const handleLogout = () => {
    logout();
    navigate('/');
  };

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-white shadow-sm border-b border-border sticky top-0 z-40">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center py-4">
            <div className="flex items-center gap-3">
              <div className="w-12 h-12 bg-gradient-to-br from-primary to-accent rounded-xl flex items-center justify-center shadow-lg">
                <span className="text-white text-2xl">M</span>
              </div>
              <div>
                <div className="text-2xl text-primary tracking-tight">MOTOTRACK</div>
                <div className="text-xs text-muted-foreground">Admin Dashboard</div>
              </div>
            </div>

            <div className="flex items-center gap-4">
              <button className="relative p-2 text-gray-600 hover:text-primary transition-colors">
                <Bell size={24} />
                <span className="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full"></span>
              </button>
              <div className="flex items-center gap-2 px-4 py-2 bg-gray-100 rounded-xl">
                <User size={18} className="text-primary" />
                <span className="text-sm text-foreground">{user?.name}</span>
              </div>
              <button
                onClick={handleLogout}
                className="flex items-center gap-2 px-4 py-2 text-red-600 hover:bg-red-50 rounded-xl transition-colors"
              >
                <LogOut size={18} />
                <span className="hidden sm:inline">Logout</span>
              </button>
            </div>
          </div>
        </div>
      </header>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Navigation Tabs */}
        <div className="mb-8 flex gap-2 overflow-x-auto pb-2">
          <button
            onClick={() => setActiveTab('overview')}
            className={`flex items-center gap-2 px-6 py-3 rounded-xl transition-all whitespace-nowrap ${
              activeTab === 'overview'
                ? 'bg-gradient-to-r from-primary to-accent text-white shadow-lg'
                : 'bg-white text-foreground hover:bg-gray-100'
            }`}
          >
            <LayoutDashboard size={20} />
            Overview
          </button>
          <button
            onClick={() => setActiveTab('stock')}
            className={`flex items-center gap-2 px-6 py-3 rounded-xl transition-all whitespace-nowrap ${
              activeTab === 'stock'
                ? 'bg-gradient-to-r from-primary to-accent text-white shadow-lg'
                : 'bg-white text-foreground hover:bg-gray-100'
            }`}
          >
            <AlertTriangle size={20} />
            Stock Monitoring
          </button>
          <button
            onClick={() => setActiveTab('inventory')}
            className={`flex items-center gap-2 px-6 py-3 rounded-xl transition-all whitespace-nowrap ${
              activeTab === 'inventory'
                ? 'bg-gradient-to-r from-primary to-accent text-white shadow-lg'
                : 'bg-white text-foreground hover:bg-gray-100'
            }`}
          >
            <Package size={20} />
            Inventory
          </button>
          <button
            onClick={() => setActiveTab('compatibility')}
            className={`flex items-center gap-2 px-6 py-3 rounded-xl transition-all whitespace-nowrap ${
              activeTab === 'compatibility'
                ? 'bg-gradient-to-r from-primary to-accent text-white shadow-lg'
                : 'bg-white text-foreground hover:bg-gray-100'
            }`}
          >
            <ClipboardCheck size={20} />
            Compatibility
          </button>
          <button
            onClick={() => setActiveTab('service')}
            className={`flex items-center gap-2 px-6 py-3 rounded-xl transition-all whitespace-nowrap ${
              activeTab === 'service'
                ? 'bg-gradient-to-r from-primary to-accent text-white shadow-lg'
                : 'bg-white text-foreground hover:bg-gray-100'
            }`}
          >
            <Wrench size={20} />
            Service Requests
          </button>
          <button
            onClick={() => setActiveTab('reports')}
            className={`flex items-center gap-2 px-6 py-3 rounded-xl transition-all whitespace-nowrap ${
              activeTab === 'reports'
                ? 'bg-gradient-to-r from-primary to-accent text-white shadow-lg'
                : 'bg-white text-foreground hover:bg-gray-100'
            }`}
          >
            <FileText size={20} />
            Reports
          </button>
        </div>

        {/* Content Area */}
        <div>
          {activeTab === 'overview' && <OverviewTab />}
          {activeTab === 'stock' && <StockMonitoringTab />}
          {activeTab === 'inventory' && <InventoryTab />}
          {activeTab === 'compatibility' && <CompatibilityTab />}
          {activeTab === 'service' && <ServiceRequestsTab />}
          {activeTab === 'reports' && <ReportsTab />}
        </div>
      </div>
    </div>
  );
}

function OverviewTab() {
  return (
    <div>
      <h2 className="text-3xl text-foreground mb-6">Dashboard Overview</h2>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div className="bg-white p-6 rounded-2xl shadow-sm border border-border">
          <div className="flex items-center justify-between mb-4">
            <div className="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
              <Package className="text-blue-600" size={24} />
            </div>
            <span className="text-sm text-green-600 bg-green-50 px-3 py-1 rounded-full">
              +12%
            </span>
          </div>
          <div className="text-3xl text-foreground mb-1">1,234</div>
          <div className="text-sm text-muted-foreground">Total Products</div>
        </div>

        <div className="bg-white p-6 rounded-2xl shadow-sm border border-border">
          <div className="flex items-center justify-between mb-4">
            <div className="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
              <AlertTriangle className="text-orange-600" size={24} />
            </div>
            <span className="text-sm text-orange-600 bg-orange-50 px-3 py-1 rounded-full">
              Low Stock
            </span>
          </div>
          <div className="text-3xl text-foreground mb-1">23</div>
          <div className="text-sm text-muted-foreground">Items Need Restock</div>
        </div>

        <div className="bg-white p-6 rounded-2xl shadow-sm border border-border">
          <div className="flex items-center justify-between mb-4">
            <div className="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
              <Wrench className="text-purple-600" size={24} />
            </div>
            <span className="text-sm text-yellow-600 bg-yellow-50 px-3 py-1 rounded-full">
              Pending
            </span>
          </div>
          <div className="text-3xl text-foreground mb-1">8</div>
          <div className="text-sm text-muted-foreground">Active Service Requests</div>
        </div>

        <div className="bg-white p-6 rounded-2xl shadow-sm border border-border">
          <div className="flex items-center justify-between mb-4">
            <div className="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
              <TrendingUp className="text-green-600" size={24} />
            </div>
            <span className="text-sm text-green-600 bg-green-50 px-3 py-1 rounded-full">
              +24%
            </span>
          </div>
          <div className="text-3xl text-foreground mb-1">₱45,890</div>
          <div className="text-sm text-muted-foreground">Revenue (Today)</div>
        </div>
      </div>

      {/* Recent Activities */}
      <div className="bg-white p-6 rounded-2xl shadow-sm border border-border">
        <h3 className="text-xl text-foreground mb-4">Recent Activities</h3>
        <div className="space-y-4">
          {[
            { action: 'New order received', time: '5 minutes ago', icon: ShoppingCart, color: 'blue' },
            { action: 'Low stock alert: Brake Pads', time: '15 minutes ago', icon: AlertTriangle, color: 'orange' },
            { action: 'Service request completed', time: '1 hour ago', icon: Wrench, color: 'green' },
            { action: 'Inventory updated: 50 items', time: '2 hours ago', icon: Package, color: 'purple' },
          ].map((activity, idx) => (
            <div key={idx} className="flex items-center gap-4 p-3 hover:bg-gray-50 rounded-xl transition-colors">
              <div className={`w-10 h-10 bg-${activity.color}-100 rounded-lg flex items-center justify-center`}>
                <activity.icon className={`text-${activity.color}-600`} size={20} />
              </div>
              <div className="flex-1">
                <div className="text-sm text-foreground">{activity.action}</div>
                <div className="text-xs text-muted-foreground">{activity.time}</div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}

function StockMonitoringTab() {
  return (
    <div>
      <h2 className="text-3xl text-foreground mb-6">Stock Monitoring</h2>
      <div className="bg-white p-6 rounded-2xl shadow-sm border border-border">
        <div className="mb-6">
          <div className="flex items-center gap-2 mb-4">
            <AlertTriangle className="text-orange-600" size={24} />
            <h3 className="text-xl text-foreground">Low Stock Alerts</h3>
          </div>
          <p className="text-muted-foreground mb-4">
            Items that need immediate attention
          </p>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full">
            <thead>
              <tr className="border-b border-border">
                <th className="text-left py-3 px-4 text-sm text-muted-foreground">Product Name</th>
                <th className="text-left py-3 px-4 text-sm text-muted-foreground">Current Stock</th>
                <th className="text-left py-3 px-4 text-sm text-muted-foreground">Min. Stock</th>
                <th className="text-left py-3 px-4 text-sm text-muted-foreground">Status</th>
                <th className="text-left py-3 px-4 text-sm text-muted-foreground">Action</th>
              </tr>
            </thead>
            <tbody>
              {[
                { name: 'Brake Pads (Honda Wave)', current: 5, min: 20, status: 'Critical' },
                { name: 'Engine Oil 10W-40', current: 15, min: 30, status: 'Low' },
                { name: 'Air Filter (Yamaha Mio)', current: 8, min: 15, status: 'Low' },
                { name: 'Spark Plug (Suzuki Raider)', current: 12, min: 25, status: 'Low' },
              ].map((item, idx) => (
                <tr key={idx} className="border-b border-border hover:bg-gray-50">
                  <td className="py-4 px-4 text-sm text-foreground">{item.name}</td>
                  <td className="py-4 px-4 text-sm text-foreground">{item.current}</td>
                  <td className="py-4 px-4 text-sm text-foreground">{item.min}</td>
                  <td className="py-4 px-4">
                    <span
                      className={`text-xs px-3 py-1 rounded-full ${
                        item.status === 'Critical'
                          ? 'bg-red-100 text-red-600'
                          : 'bg-orange-100 text-orange-600'
                      }`}
                    >
                      {item.status}
                    </span>
                  </td>
                  <td className="py-4 px-4">
                    <button className="text-sm text-primary hover:underline">Restock</button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}

function InventoryTab() {
  return (
    <div>
      <h2 className="text-3xl text-foreground mb-6">Inventory Management</h2>
      <div className="bg-white p-6 rounded-2xl shadow-sm border border-border">
        <div className="flex justify-between items-center mb-6">
          <div>
            <h3 className="text-xl text-foreground mb-2">Product Inventory</h3>
            <p className="text-sm text-muted-foreground">Manage your motorcycle parts and accessories</p>
          </div>
          <button className="bg-gradient-to-r from-primary to-accent text-white px-6 py-3 rounded-xl hover:shadow-lg transition-all">
            + Add Product
          </button>
        </div>

        <div className="mb-4">
          <input
            type="text"
            placeholder="Search products..."
            className="w-full px-4 py-3 border-2 border-border rounded-xl focus:outline-none focus:border-primary"
          />
        </div>

        <div className="overflow-x-auto">
          <table className="w-full">
            <thead>
              <tr className="border-b border-border">
                <th className="text-left py-3 px-4 text-sm text-muted-foreground">Product</th>
                <th className="text-left py-3 px-4 text-sm text-muted-foreground">Category</th>
                <th className="text-left py-3 px-4 text-sm text-muted-foreground">Stock</th>
                <th className="text-left py-3 px-4 text-sm text-muted-foreground">Price</th>
                <th className="text-left py-3 px-4 text-sm text-muted-foreground">Actions</th>
              </tr>
            </thead>
            <tbody>
              {[
                { name: 'Brake Pads (Honda Wave)', category: 'Brake System', stock: 45, price: 450 },
                { name: 'Engine Oil 10W-40', category: 'Lubricants', stock: 120, price: 380 },
                { name: 'Air Filter (Yamaha Mio)', category: 'Filters', stock: 35, price: 250 },
                { name: 'Chain Sprocket Set', category: 'Drive Train', stock: 28, price: 1200 },
              ].map((item, idx) => (
                <tr key={idx} className="border-b border-border hover:bg-gray-50">
                  <td className="py-4 px-4 text-sm text-foreground">{item.name}</td>
                  <td className="py-4 px-4 text-sm text-muted-foreground">{item.category}</td>
                  <td className="py-4 px-4 text-sm text-foreground">{item.stock}</td>
                  <td className="py-4 px-4 text-sm text-foreground">₱{item.price}</td>
                  <td className="py-4 px-4">
                    <div className="flex gap-2">
                      <button className="text-sm text-blue-600 hover:underline">Edit</button>
                      <button className="text-sm text-red-600 hover:underline">Delete</button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}

function CompatibilityTab() {
  return (
    <div>
      <h2 className="text-3xl text-foreground mb-6">Compatibility Management</h2>
      <div className="bg-white p-6 rounded-2xl shadow-sm border border-border">
        <div className="mb-6">
          <h3 className="text-xl text-foreground mb-2">Part Compatibility Database</h3>
          <p className="text-sm text-muted-foreground">
            Manage which parts are compatible with specific motorcycle models
          </p>
        </div>

        <div className="mb-6 p-4 bg-blue-50 rounded-xl">
          <div className="flex items-start gap-3">
            <ClipboardCheck className="text-blue-600 mt-1" size={20} />
            <div>
              <div className="text-sm text-foreground mb-1">Automated Sales Recording</div>
              <div className="text-xs text-muted-foreground">
                When customers check compatibility and purchase, the system automatically records the sale
              </div>
            </div>
          </div>
        </div>

        <button className="bg-gradient-to-r from-primary to-accent text-white px-6 py-3 rounded-xl hover:shadow-lg transition-all mb-6">
          + Add Compatibility Entry
        </button>

        <div className="space-y-4">
          {[
            { part: 'Brake Pads BP-001', makes: ['Honda', 'Kawasaki'], models: ['Wave 110', 'TMX 155', 'CT 100'] },
            { part: 'Engine Oil 10W-40', makes: ['All Makes'], models: ['All Models'] },
            { part: 'Air Filter AF-Y50', makes: ['Yamaha'], models: ['Mio i125', 'Mio Soul', 'Aerox'] },
          ].map((item, idx) => (
            <div key={idx} className="p-4 border-2 border-border rounded-xl hover:border-primary transition-colors">
              <div className="text-foreground mb-2">{item.part}</div>
              <div className="text-sm text-muted-foreground mb-1">
                Compatible Makes: {item.makes.join(', ')}
              </div>
              <div className="text-sm text-muted-foreground mb-3">
                Models: {item.models.join(', ')}
              </div>
              <div className="flex gap-2">
                <button className="text-sm text-blue-600 hover:underline">Edit</button>
                <button className="text-sm text-red-600 hover:underline">Remove</button>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}

function ServiceRequestsTab() {
  return (
    <div>
      <h2 className="text-3xl text-foreground mb-6">Service & Repair Requests</h2>
      <div className="bg-white p-6 rounded-2xl shadow-sm border border-border">
        <div className="mb-6">
          <h3 className="text-xl text-foreground mb-2">Active Requests</h3>
          <p className="text-sm text-muted-foreground">Track and manage customer service requests</p>
        </div>

        <div className="space-y-4">
          {[
            {
              id: 'SR-001',
              customer: 'Juan Dela Cruz',
              motorcycle: 'Honda Wave 110',
              service: 'Oil Change + Brake Adjustment',
              status: 'In Progress',
              date: '2026-04-13',
            },
            {
              id: 'SR-002',
              customer: 'Maria Santos',
              motorcycle: 'Yamaha Mio i125',
              service: 'General Check-up',
              status: 'Pending',
              date: '2026-04-13',
            },
            {
              id: 'SR-003',
              customer: 'Pedro Garcia',
              motorcycle: 'Suzuki Raider R150',
              service: 'Chain Replacement',
              status: 'Completed',
              date: '2026-04-12',
            },
          ].map((request, idx) => (
            <div
              key={idx}
              className="p-4 border-2 border-border rounded-xl hover:border-primary transition-colors"
            >
              <div className="flex justify-between items-start mb-3">
                <div>
                  <div className="text-foreground mb-1">
                    {request.id} - {request.customer}
                  </div>
                  <div className="text-sm text-muted-foreground">{request.motorcycle}</div>
                </div>
                <span
                  className={`text-xs px-3 py-1 rounded-full ${
                    request.status === 'Completed'
                      ? 'bg-green-100 text-green-600'
                      : request.status === 'In Progress'
                      ? 'bg-blue-100 text-blue-600'
                      : 'bg-yellow-100 text-yellow-600'
                  }`}
                >
                  {request.status}
                </span>
              </div>
              <div className="text-sm text-foreground mb-2">{request.service}</div>
              <div className="text-xs text-muted-foreground mb-3">Requested: {request.date}</div>
              <div className="flex gap-2">
                <button className="text-sm text-blue-600 hover:underline">View Details</button>
                {request.status !== 'Completed' && (
                  <button className="text-sm text-green-600 hover:underline">Mark Complete</button>
                )}
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}

function ReportsTab() {
  return (
    <div>
      <h2 className="text-3xl text-foreground mb-6">Reports & Analytics</h2>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div className="bg-white p-6 rounded-2xl shadow-sm border border-border">
          <h3 className="text-xl text-foreground mb-4">Generate Report</h3>
          <div className="space-y-4">
            <div>
              <label className="block text-sm text-foreground mb-2">Report Type</label>
              <select className="w-full px-4 py-3 border-2 border-border rounded-xl focus:outline-none focus:border-primary bg-white">
                <option>Sales Report</option>
                <option>Inventory Report</option>
                <option>Service Report</option>
                <option>Revenue Report</option>
              </select>
            </div>
            <div>
              <label className="block text-sm text-foreground mb-2">Date Range</label>
              <select className="w-full px-4 py-3 border-2 border-border rounded-xl focus:outline-none focus:border-primary bg-white">
                <option>Today</option>
                <option>Last 7 Days</option>
                <option>Last 30 Days</option>
                <option>This Month</option>
                <option>Last Month</option>
                <option>Custom Range</option>
              </select>
            </div>
            <button className="w-full bg-gradient-to-r from-primary to-accent text-white py-3 rounded-xl hover:shadow-lg transition-all">
              Generate Report
            </button>
          </div>
        </div>

        <div className="bg-white p-6 rounded-2xl shadow-sm border border-border">
          <h3 className="text-xl text-foreground mb-4">Quick Stats</h3>
          <div className="space-y-4">
            <div className="flex justify-between items-center p-3 bg-gray-50 rounded-xl">
              <span className="text-sm text-muted-foreground">Total Sales (This Month)</span>
              <span className="text-lg text-foreground">₱125,450</span>
            </div>
            <div className="flex justify-between items-center p-3 bg-gray-50 rounded-xl">
              <span className="text-sm text-muted-foreground">Orders Completed</span>
              <span className="text-lg text-foreground">87</span>
            </div>
            <div className="flex justify-between items-center p-3 bg-gray-50 rounded-xl">
              <span className="text-sm text-muted-foreground">Services Done</span>
              <span className="text-lg text-foreground">45</span>
            </div>
            <div className="flex justify-between items-center p-3 bg-gray-50 rounded-xl">
              <span className="text-sm text-muted-foreground">Revenue Growth</span>
              <span className="text-lg text-green-600">+24%</span>
            </div>
          </div>
        </div>
      </div>

      <div className="bg-white p-6 rounded-2xl shadow-sm border border-border">
        <h3 className="text-xl text-foreground mb-4">Recent Reports</h3>
        <div className="space-y-3">
          {[
            { name: 'Sales Report - March 2026', date: '2026-04-01', type: 'Sales' },
            { name: 'Inventory Summary - Q1 2026', date: '2026-04-01', type: 'Inventory' },
            { name: 'Service Revenue - February 2026', date: '2026-03-01', type: 'Revenue' },
          ].map((report, idx) => (
            <div
              key={idx}
              className="flex justify-between items-center p-4 border-2 border-border rounded-xl hover:border-primary transition-colors"
            >
              <div>
                <div className="text-foreground mb-1">{report.name}</div>
                <div className="text-xs text-muted-foreground">Generated: {report.date}</div>
              </div>
              <button className="text-sm text-primary hover:underline">Download PDF</button>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}

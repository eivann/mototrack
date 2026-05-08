import { createBrowserRouter } from "react-router";
import { LandingPage } from "./pages/LandingPage";
import { ProductsPage } from "./pages/ProductsPage";
import { AdminDashboard } from "./pages/AdminDashboard";

export const router = createBrowserRouter([
  {
    path: "/",
    Component: LandingPage,
  },
  {
    path: "/products",
    Component: ProductsPage,
  },
  {
    path: "/admin",
    Component: AdminDashboard,
  },
]);

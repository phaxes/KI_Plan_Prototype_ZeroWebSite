import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import Header from './components/layout/Header';
import Footer from './components/layout/Footer';
import Home from './pages/Home';
import Blog from './pages/Blog';
import Shop from './pages/Shop';
import Auth from './pages/Auth';
import Kanban from './pages/Kanban';

export default function App() {
  return (
    <Router>
      <div className="flex flex-col min-h-screen bg-slate-50 text-slate-900 font-sans">
        <Header />
        <main className="flex-grow">
          <Routes>
            <Route path="/" element={<Home />} />
            <Route path="/blog" element={<Blog />} />
            <Route path="/shop" element={<Shop />} />
            <Route path="/auth" element={<Auth />} />
            <Route path="/kanban" element={<Kanban />} />
          </Routes>
        </main>
        <Footer />
      </div>
    </Router>
  );
}

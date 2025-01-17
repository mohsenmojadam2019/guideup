import React, { Component } from 'react';
import { NavLink } from 'react-router-dom'

class Sidebar extends Component {

  handleClick(e) {
    e.preventDefault();
    e.target.parentElement.classList.toggle('open');
  }

  activeRoute(routeName) {
    return this.props.location.pathname.indexOf(routeName) > -1 ? 'nav-item nav-dropdown open' : 'nav-item nav-dropdown';
  }

  // secondLevelActive(routeName) {
  //   return this.props.location.pathname.indexOf(routeName) > -1 ? "nav nav-second-level collapse in" : "nav nav-second-level collapse";
  // }

  render() {
    return (

      <div className="sidebar">
        <nav className="sidebar-nav">
          <ul className="nav">
            <li className="nav-item">
              <NavLink to={'/dashboard'} className="nav-link" activeClassName="active"><i className="icon-speedometer"></i> Principal</NavLink>
              <NavLink to={'/place'} className="nav-link" activeClassName="active"><i className="icon-speedometer"></i> Lugares <span className="badge badge-info">NEW</span></NavLink>
              <NavLink to={'/user'} className="nav-link" activeClassName="active"><i className="icon-speedometer"></i> Usuários <span className="badge badge-info">NEW</span></NavLink>
              <NavLink to={'/guide'} className="nav-link" activeClassName="active"><i className="icon-speedometer"></i> Guias <span className="badge badge-info">NEW</span></NavLink>
              <NavLink to={'/feedback'} className="nav-link" activeClassName="active"><i className="icon-speedometer"></i> Comentários <span className="badge badge-info">NEW</span></NavLink>
            </li>
          </ul>
        </nav>
      </div>
    )
  }
}

export default Sidebar;

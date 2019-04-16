class AdminController < ApplicationController
  before_filter :authenticate_user!
  before_filter :check_user_is_admin

  def users
    @users = User.all.page(params[:page]).per(10)
  end
end

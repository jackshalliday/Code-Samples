class ApplicationController < ActionController::Base
  protect_from_forgery with: :exception
  before_action with: :authenticate_user!

  def check_user_is_admin
    if !current_user.is_admin?
      flash[:error] = 'You do not have permission to access this area at this time'
      redirect_to root_path
    end
  end
end

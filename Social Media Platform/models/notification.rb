class Notification
  include Neo4j::ActiveNode
  include Neo4j::Timestamps

  property :message, type: String, index: :exact
  validates :message, presence: true

  has_one :out, :recipient, type: :user, model_class: :User
  has_one :in, :subject, origin: :post, model_class: :Post
end
